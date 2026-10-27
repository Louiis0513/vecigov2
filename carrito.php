<?php
require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/conexion.php';
iniciar_sesion();
exigir_cliente();

// Acciones del carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $idProducto = (int) ($_POST['id_producto'] ?? 0);
    $idComercio = (int) ($_POST['id_comercio'] ?? 0);
    $volver = (string) ($_POST['volver'] ?? '');

    if ($accion === 'agregar' && $idProducto > 0 && $idComercio > 0) {
        $estado = carrito_estado();
        if ($estado['id_comercio'] > 0 && $estado['id_comercio'] !== $idComercio && count($estado['items']) > 0) {
            carrito_vaciar();
            $_SESSION['mensaje'] = 'Empezamos un carrito nuevo en este local.';
        }
        $chk = $mysqli->prepare(
            'SELECT id_producto FROM producto WHERE id_producto = ? AND id_comercio = ? AND disponible = 1'
        );
        $chk->bind_param('ii', $idProducto, $idComercio);
        $chk->execute();
        if ($chk->get_result()->fetch_assoc()) {
            carrito_agregar($idComercio, $idProducto, max(1, (int) ($_POST['cantidad'] ?? 1)));
            if (empty($_SESSION['mensaje'])) {
                $_SESSION['mensaje'] = 'Producto agregado al carrito.';
            }
        }
        if ($volver === 'carrito') {
            header('Location: carrito.php');
        } else {
            header('Location: comercio.php?id=' . $idComercio . '#catalogo');
        }
        exit;
    }

    if ($accion === 'actualizar' && $idProducto > 0) {
        carrito_fijar($idProducto, (int) ($_POST['cantidad'] ?? 0));
        header('Location: carrito.php');
        exit;
    }

    if ($accion === 'quitar' && $idProducto > 0) {
        carrito_fijar($idProducto, 0);
        $_SESSION['mensaje'] = 'Producto quitado.';
        header('Location: carrito.php');
        exit;
    }

    if ($accion === 'vaciar') {
        carrito_vaciar();
        $_SESSION['mensaje'] = 'Carrito vacío.';
        header('Location: carrito.php');
        exit;
    }

    if ($accion === 'pedir') {
        $items = carrito_detalle($mysqli);
        $idComercioCart = carrito_id_comercio();
        $nombreCliente = trim($_POST['nombre_cliente'] ?? '');
        $telefonoCliente = trim($_POST['telefono_cliente'] ?? '');
        $direccionEntrega = trim($_POST['direccion_entrega'] ?? '');
        $errores = [];

        if ($idComercioCart <= 0 || count($items) === 0) {
            $errores['general'] = 'Tu carrito está vacío.';
        }
        if (mb_strlen($nombreCliente) < 3 || mb_strlen($nombreCliente) > 100) {
            $errores['nombre_cliente'] = 'Escribe tu nombre (entre 3 y 100 letras)';
        }
        if (!preg_match('/^3\d{9}$/', $telefonoCliente)) {
            $errores['telefono_cliente'] = 'Celular de 10 dígitos que empiece por 3';
        }
        if (mb_strlen($direccionEntrega) < 5 || mb_strlen($direccionEntrega) > 150) {
            $errores['direccion_entrega'] = 'Escribe la dirección completa';
        }

        if (count($errores) === 0) {
            $lineas = [];
            $itemsPedido = [];
            foreach ($items as $it) {
                $lineas[] = $it['cantidad'] . ' × ' . $it['nombre'];
                $itemsPedido[] = [
                    'id_producto' => $it['id_producto'],
                    'nombre' => $it['nombre'],
                    'cantidad' => $it['cantidad'],
                    'precio' => $it['precio'],
                ];
            }
            $detalle = implode(' · ', $lineas);

            try {
                $mysqli->begin_transaction();
                $idUsuarioPedido = cliente_logueado() ? cliente_id_sesion() : null;
                if ($idUsuarioPedido) {
                    $stmt = $mysqli->prepare(
                        'INSERT INTO pedido (nombre_cliente, telefono_cliente, direccion_entrega, detalle, id_comercio, id_usuario)
                         VALUES (?, ?, ?, ?, ?, ?)'
                    );
                    $stmt->bind_param(
                        'ssssii',
                        $nombreCliente,
                        $telefonoCliente,
                        $direccionEntrega,
                        $detalle,
                        $idComercioCart,
                        $idUsuarioPedido
                    );
                } else {
                    $stmt = $mysqli->prepare(
                        'INSERT INTO pedido (nombre_cliente, telefono_cliente, direccion_entrega, detalle, id_comercio)
                         VALUES (?, ?, ?, ?, ?)'
                    );
                    $stmt->bind_param(
                        'ssssi',
                        $nombreCliente,
                        $telefonoCliente,
                        $direccionEntrega,
                        $detalle,
                        $idComercioCart
                    );
                }
                $stmt->execute();
                $idPedido = (int) $stmt->insert_id;
                guardar_items_pedido($mysqli, $idPedido, $itemsPedido);
                $mysqli->commit();
                cacheParcheTrasPedido($mysqli);
                carrito_vaciar();

                if (cliente_logueado()) {
                    $uid = cliente_id_sesion();
                    $up = $mysqli->prepare(
                        "UPDATE usuario SET telefono = IF(telefono IS NULL OR telefono = '', ?, telefono),
                                           direccion = IF(direccion IS NULL OR direccion = '', ?, direccion)
                         WHERE id_usuario = ? AND rol = 'cliente'"
                    );
                    $up->bind_param('ssi', $telefonoCliente, $direccionEntrega, $uid);
                    $up->execute();
                    refrescar_sesion_cliente($mysqli);
                    $_SESSION['mensaje'] = '¡Pedido #' . $idPedido . ' enviado!';
                    header('Location: cliente/pedidos.php');
                } else {
                    $_SESSION['mensaje'] = '¡Pedido #' . $idPedido . ' enviado! Entra para seguirlo.';
                    header('Location: login.php');
                }
                exit;
            } catch (mysqli_sql_exception $e) {
                $mysqli->rollback();
                $errores['general'] = 'No se pudo enviar el pedido. Intenta de nuevo.';
            }
        }
        $_SESSION['carrito_errores'] = $errores;
        $_SESSION['carrito_form'] = compact('nombreCliente', 'telefonoCliente', 'direccionEntrega');
        header('Location: carrito.php#checkout');
        exit;
    }
}

$items = carrito_detalle($mysqli);
$idComercioCart = carrito_id_comercio();
$comercio = null;
if ($idComercioCart > 0) {
    $st = $mysqli->prepare(
        'SELECT c.id_comercio, c.nombre, c.foto_url, cat.nombre AS categoria
         FROM comercio c
         JOIN categoria_comercio cat ON cat.id_categoria_comercio = c.id_categoria_comercio
         WHERE c.id_comercio = ?'
    );
    $st->bind_param('i', $idComercioCart);
    $st->execute();
    $comercio = $st->get_result()->fetch_assoc();
}

$total = carrito_total($mysqli);
$errores = $_SESSION['carrito_errores'] ?? [];
$form = $_SESSION['carrito_form'] ?? [];
unset($_SESSION['carrito_errores'], $_SESSION['carrito_form']);

$nombreCliente = $form['nombreCliente'] ?? '';
$telefonoCliente = $form['telefonoCliente'] ?? '';
$direccionEntrega = $form['direccionEntrega'] ?? '';
if ($nombreCliente === '' && cliente_logueado()) {
    refrescar_sesion_cliente($mysqli);
    $nombreCliente = (string) ($_SESSION['cliente_nombre'] ?? '');
    $telefonoCliente = (string) ($_SESSION['cliente_telefono'] ?? '');
    $direccionEntrega = (string) ($_SESSION['cliente_direccion'] ?? '');
}

$mensaje = $_SESSION['mensaje'] ?? null;
unset($_SESSION['mensaje']);

$tituloPagina = 'Carrito · VeciGo';
require __DIR__ . '/includes/header.php';
?>

<div class="contenedor">
    <div class="page-head">
        <div>
            <h1>Tu carrito</h1>
            <p><?= count($items) ?> producto(s) · termina y envía el pedido</p>
        </div>
        <?php if ($comercio): ?>
            <a class="btn btn-line" href="comercio.php?id=<?= (int) $comercio['id_comercio'] ?>">← Seguir comprando</a>
        <?php else: ?>
            <a class="btn btn-line" href="locales.php">← Ver locales</a>
        <?php endif; ?>
    </div>

    <?php if ($mensaje): ?>
        <div class="alerta alerta-exito"><?= e($mensaje) ?></div>
    <?php endif; ?>
    <?php if (isset($errores['general'])): ?>
        <div class="alerta alerta-error"><?= e($errores['general']) ?></div>
    <?php endif; ?>

    <?php if (count($items) === 0 || !$comercio): ?>
        <div class="card">
            <p class="vacio">El carrito está vacío.</p>
            <a class="btn btn-coral" href="locales.php">Explorar locales</a>
        </div>
    <?php else: ?>
        <div class="carrito-layout">
            <div class="card carrito-lista">
                <div class="carrito-store">
                    <img src="<?= e(foto_url($comercio['foto_url'])) ?>" alt="" width="48" height="48">
                    <div>
                        <strong><?= e($comercio['nombre']) ?></strong>
                        <p class="muted"><?= e($comercio['categoria']) ?> · <?= count($items) ?> producto(s)</p>
                    </div>
                </div>

                <?php foreach ($items as $it): ?>
                    <article class="carrito-item">
                        <img src="<?= e(foto_url($it['foto_url'], 'img/hero/barrio.jpg')) ?>" alt="<?= e($it['nombre']) ?>">
                        <div class="carrito-item__info">
                            <h3><?= e($it['nombre']) ?></h3>
                            <p class="precio"><?= formatearMoneda($it['precio']) ?></p>
                            <div class="carrito-item__actions">
                                <form method="post" action="carrito.php" class="qty-form">
                                    <input type="hidden" name="accion" value="actualizar">
                                    <input type="hidden" name="id_producto" value="<?= (int) $it['id_producto'] ?>">
                                    <label class="sr-only" for="qty<?= (int) $it['id_producto'] ?>">Cantidad</label>
                                    <input type="number" id="qty<?= (int) $it['id_producto'] ?>" name="cantidad"
                                           value="<?= (int) $it['cantidad'] ?>" min="1" max="99"
                                           onchange="this.form.submit()">
                                </form>
                                <form method="post" action="carrito.php">
                                    <input type="hidden" name="accion" value="quitar">
                                    <input type="hidden" name="id_producto" value="<?= (int) $it['id_producto'] ?>">
                                    <button type="submit" class="btn btn-sm btn-line">Quitar</button>
                                </form>
                            </div>
                        </div>
                        <p class="carrito-item__sub"><?= formatearMoneda($it['subtotal']) ?></p>
                    </article>
                <?php endforeach; ?>

                <form method="post" action="carrito.php" style="margin-top:1rem;">
                    <input type="hidden" name="accion" value="vaciar">
                    <button type="submit" class="btn btn-sm btn-line">Vaciar carrito</button>
                </form>
            </div>

            <div class="card carrito-checkout" id="checkout">
                <h2 style="margin-bottom:0.5rem;">Finalizar pedido</h2>
                <p class="muted" style="margin-bottom:1rem;">Total: <strong class="precio"><?= formatearMoneda($total) ?></strong></p>

                <form method="post" action="carrito.php">
                    <input type="hidden" name="accion" value="pedir">
                    <div class="campo">
                        <label for="nombre_cliente">Tu nombre</label>
                        <input type="text" id="nombre_cliente" name="nombre_cliente"
                               value="<?= e($nombreCliente) ?>" required
                               class="<?= isset($errores['nombre_cliente']) ? 'invalido' : '' ?>">
                        <?php if (isset($errores['nombre_cliente'])): ?>
                            <span class="error"><?= e($errores['nombre_cliente']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="campo">
                        <label for="telefono_cliente">Celular</label>
                        <input type="tel" id="telefono_cliente" name="telefono_cliente"
                               value="<?= e($telefonoCliente) ?>" required placeholder="3001234567"
                               class="<?= isset($errores['telefono_cliente']) ? 'invalido' : '' ?>">
                        <?php if (isset($errores['telefono_cliente'])): ?>
                            <span class="error"><?= e($errores['telefono_cliente']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="campo">
                        <label for="direccion_entrega">Dirección de entrega</label>
                        <input type="text" id="direccion_entrega" name="direccion_entrega"
                               value="<?= e($direccionEntrega) ?>" required
                               class="<?= isset($errores['direccion_entrega']) ? 'invalido' : '' ?>">
                        <?php if (isset($errores['direccion_entrega'])): ?>
                            <span class="error"><?= e($errores['direccion_entrega']) ?></span>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-coral btn-block" type="submit">Enviar pedido</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
