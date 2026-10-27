<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../funciones.php';
require_once __DIR__ . '/../conexion.php';
exigir_cliente();
refrescar_sesion_cliente($mysqli);

$idCliente = cliente_id_sesion();
$telefono = (string) ($_SESSION['cliente_telefono'] ?? '');
$estadosValidos = ['nuevo', 'confirmado', 'entregado', 'cancelado'];

$filtro = $_GET['estado'] ?? '';
if ($filtro !== '' && !in_array($filtro, $estadosValidos, true)) {
    $filtro = '';
}

if ($filtro !== '') {
    $stmt = $mysqli->prepare(
        "SELECT p.id_pedido, p.nombre_cliente, p.direccion_entrega, p.detalle, p.estado, p.fecha,
                c.nombre AS comercio, c.telefono AS tel_comercio
         FROM pedido p
         JOIN comercio c ON c.id_comercio = p.id_comercio
         WHERE (p.id_usuario = ? OR (? <> '' AND p.telefono_cliente = ?)) AND p.estado = ?
         ORDER BY p.fecha DESC"
    );
    $stmt->bind_param('isss', $idCliente, $telefono, $telefono, $filtro);
} else {
    $stmt = $mysqli->prepare(
        "SELECT p.id_pedido, p.nombre_cliente, p.direccion_entrega, p.detalle, p.estado, p.fecha,
                c.nombre AS comercio, c.telefono AS tel_comercio
         FROM pedido p
         JOIN comercio c ON c.id_comercio = p.id_comercio
         WHERE p.id_usuario = ? OR (? <> '' AND p.telefono_cliente = ?)
         ORDER BY p.fecha DESC"
    );
    $stmt->bind_param('iss', $idCliente, $telefono, $telefono);
}
$stmt->execute();
$pedidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$itemsPorPedido = [];
if (count($pedidos) > 0) {
    $ids = array_map(static fn ($p) => (int) $p['id_pedido'], $pedidos);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    try {
        $stItems = $mysqli->prepare(
            "SELECT id_pedido, nombre, cantidad, precio_unitario
             FROM pedido_item WHERE id_pedido IN ($placeholders) ORDER BY id_pedido_item"
        );
        $stItems->bind_param($types, ...$ids);
        $stItems->execute();
        foreach ($stItems->get_result()->fetch_all(MYSQLI_ASSOC) as $it) {
            $itemsPorPedido[(int) $it['id_pedido']][] = $it;
        }
    } catch (mysqli_sql_exception $e) {
        // sin tabla
    }
}

$tituloPagina = 'Mis pedidos · VeciGo';
require __DIR__ . '/../includes/header.php';
?>

<div class="contenedor">
    <div class="page-head">
        <div>
            <h1>Mis pedidos</h1>
            <p><?= count($pedidos) ?> pedido(s) vinculados a tu cuenta<?= $telefono !== '' ? ' o celular' : '' ?>.</p>
        </div>
        <a class="btn btn-line" href="index.php">← Mi cuenta</a>
    </div>

    <div class="filtros-bar">
        <a href="pedidos.php" class="<?= $filtro === '' ? 'activo' : '' ?>">Todos</a>
        <?php foreach ($estadosValidos as $est): ?>
            <a href="pedidos.php?estado=<?= e($est) ?>" class="<?= $filtro === $est ? 'activo' : '' ?>">
                <?= e(etiqueta_estado($est)) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (count($pedidos) === 0): ?>
        <div class="card">
            <p class="vacio">No hay pedidos con este filtro.</p>
            <a class="btn btn-coral" href="<?= e(base_path()) ?>index.php#categorias">Pedir en el barrio</a>
        </div>
    <?php else: ?>
        <div class="grid-pedidos-cliente">
            <?php foreach ($pedidos as $ped): ?>
                <?php
                $idPed = (int) $ped['id_pedido'];
                $lineas = $itemsPorPedido[$idPed] ?? [];
                $total = 0.0;
                foreach ($lineas as $it) {
                    $total += (float) $it['precio_unitario'] * (int) $it['cantidad'];
                }
                ?>
                <article class="card pedido-cliente">
                    <div class="pedido-cliente__head">
                        <div>
                            <span class="badge badge-estado-<?= e($ped['estado']) ?>">
                                <?= e(etiqueta_estado($ped['estado'])) ?>
                            </span>
                            <h2>Pedido #<?= $idPed ?></h2>
                            <p class="muted"><?= e($ped['comercio']) ?> · <?= date('d/m/Y H:i', strtotime($ped['fecha'])) ?></p>
                        </div>
                        <a class="btn btn-ws btn-sm" target="_blank" rel="noopener"
                           href="<?= e(enlaceWhatsApp($ped['tel_comercio'], 'Hola, escribo por mi pedido VeciGo #' . $idPed)) ?>">
                            WhatsApp al local
                        </a>
                    </div>
                    <p><strong>Entrega:</strong> <?= e($ped['direccion_entrega']) ?></p>
                    <?php if (count($lineas) > 0): ?>
                        <ul class="lista-items">
                            <?php foreach ($lineas as $it): ?>
                                <li>
                                    <?= (int) $it['cantidad'] ?> × <?= e($it['nombre']) ?>
                                    <span class="muted"><?= formatearMoneda((float) $it['precio_unitario']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if ($total > 0): ?>
                            <p class="precio" style="margin-top:0.75rem;">Total estimado: <?= formatearMoneda($total) ?></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p><?= e($ped['detalle']) ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
