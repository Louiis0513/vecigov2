<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../funciones.php';
require_once __DIR__ . '/../conexion.php';
exigir_comercio();

$idComercio = comercio_id_sesion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $idProducto = (int) ($_POST['id_producto'] ?? 0);

    if ($accion === 'toggle' && $idProducto > 0) {
        $stmt = $mysqli->prepare(
            'UPDATE producto SET disponible = IF(disponible = 1, 0, 1)
             WHERE id_producto = ? AND id_comercio = ?'
        );
        $stmt->bind_param('ii', $idProducto, $idComercio);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            cacheParcheProducto($mysqli, $idComercio, $idProducto);
        }
        $_SESSION['mensaje'] = 'Disponibilidad actualizada.';
    }

    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $precioRaw = preg_replace('/[^\d]/', '', (string) ($_POST['precio'] ?? '0')) ?? '0';
        $precio = (float) $precioRaw;
        $descripcion = trim($_POST['descripcion'] ?? '');

        $errores = [];
        if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100) {
            $errores[] = 'Nombre entre 2 y 100 caracteres.';
        }
        if ($precio < 100 || $precio > 9999999) {
            $errores[] = 'Precio inválido.';
        }
        if (count($errores) === 0) {
            $stmt = $mysqli->prepare(
                'INSERT INTO producto (nombre, precio, descripcion, disponible, id_comercio)
                 VALUES (?, ?, ?, 1, ?)'
            );
            $stmt->bind_param('sdsi', $nombre, $precio, $descripcion, $idComercio);
            $stmt->execute();
            $nuevoId = (int) $stmt->insert_id;
            cacheParcheProducto($mysqli, $idComercio, $nuevoId > 0 ? $nuevoId : null);
            $_SESSION['mensaje'] = 'Producto «' . $nombre . '» agregado al menú.';
        } else {
            $_SESSION['mensaje_error'] = implode(' ', $errores);
        }
    }

    header('Location: productos.php');
    exit;
}

$stmt = $mysqli->prepare(
    'SELECT id_producto, nombre, precio, descripcion, disponible, foto_url
     FROM producto
     WHERE id_comercio = ?
     ORDER BY disponible DESC, nombre'
);
$stmt->bind_param('i', $idComercio);
$stmt->execute();
$productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$mensaje = $_SESSION['mensaje'] ?? null;
$mensajeError = $_SESSION['mensaje_error'] ?? null;
unset($_SESSION['mensaje'], $_SESSION['mensaje_error']);

$tituloPagina = 'Menú · ' . ($_SESSION['comercio_local'] ?? 'Comercio');
require __DIR__ . '/../includes/header.php';
?>

<div class="contenedor">
    <div class="page-head">
        <div>
            <h1>Mi menú</h1>
            <p>Marca agotados o agrega productos. <?= count($productos) ?> en catálogo.</p>
        </div>
        <a class="btn btn-line" href="index.php">← Panel</a>
    </div>

    <?php if ($mensaje): ?>
        <div class="alerta alerta-exito"><?= e($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($mensajeError): ?>
        <div class="alerta alerta-error"><?= e($mensajeError) ?></div>
    <?php endif; ?>

    <div class="pedido-layout" style="margin-bottom:2rem;">
        <div class="card">
            <h2 style="margin-bottom:1rem;">Agregar producto</h2>
            <form method="post" action="productos.php">
                <input type="hidden" name="accion" value="crear">
                <div class="campo">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" required maxlength="100">
                </div>
                <div class="campo">
                    <label for="precio">Precio (COP)</label>
                    <input type="text" id="precio" name="precio" placeholder="15000" required>
                </div>
                <div class="campo">
                    <label for="descripcion">Descripción (opcional)</label>
                    <textarea id="descripcion" name="descripcion" rows="2" maxlength="500"></textarea>
                </div>
                <button class="btn btn-coral" type="submit">Guardar en el menú</button>
            </form>
        </div>

        <div class="card">
            <h2 style="margin-bottom:0.5rem;">Tip</h2>
            <p style="color:var(--muted);font-size:0.95rem;">
                Si se te acaba un plato, márcalo como <strong>Agotado</strong>.
                El cliente ya no podrá agregarlo al pedido.
            </p>
        </div>
    </div>

    <?php if (count($productos) === 0): ?>
        <div class="card"><p class="vacio">Todavía no tienes productos. Agrega el primero arriba.</p></div>
    <?php else: ?>
        <div class="grid-productos">
            <?php foreach ($productos as $prod): ?>
                <article class="prod-card <?= $prod['disponible'] ? '' : 'agotado' ?>">
                    <div class="prod-card__foto">
                        <img src="<?= e(foto_url($prod['foto_url'], 'img/hero/barrio.jpg')) ?>"
                             alt="<?= e($prod['nombre']) ?>" loading="lazy">
                    </div>
                    <div class="prod-card__body">
                        <?php if ($prod['disponible']): ?>
                            <span class="badge badge-ok">Disponible</span>
                        <?php else: ?>
                            <span class="badge badge-alerta">Agotado</span>
                        <?php endif; ?>
                        <h3><?= e($prod['nombre']) ?></h3>
                        <p style="color:var(--muted);font-size:0.9rem;"><?= e($prod['descripcion'] ?? '') ?></p>
                        <p class="precio"><?= formatearMoneda((float) $prod['precio']) ?></p>
                        <form method="post" action="productos.php">
                            <input type="hidden" name="accion" value="toggle">
                            <input type="hidden" name="id_producto" value="<?= (int) $prod['id_producto'] ?>">
                            <button class="btn btn-sm <?= $prod['disponible'] ? 'btn-line' : 'btn-coral' ?>" type="submit">
                                <?= $prod['disponible'] ? 'Marcar agotado' : 'Volver a disponible' ?>
                            </button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
