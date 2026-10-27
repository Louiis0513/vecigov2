<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../funciones.php';
require_once __DIR__ . '/../conexion.php';
exigir_comercio();

$idComercio = comercio_id_sesion();
$estadosValidos = ['nuevo', 'confirmado', 'entregado', 'cancelado'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPedido = (int) ($_POST['id_pedido'] ?? 0);
    $estado = $_POST['estado'] ?? '';
    if ($idPedido > 0 && in_array($estado, $estadosValidos, true)) {
        $stmt = $mysqli->prepare(
            'UPDATE pedido SET estado = ? WHERE id_pedido = ? AND id_comercio = ?'
        );
        $stmt->bind_param('sii', $estado, $idPedido, $idComercio);
        $stmt->execute();
        if ($stmt->affected_rows >= 0) {
            $_SESSION['mensaje'] = 'Pedido #' . $idPedido . ' → «' . etiqueta_estado($estado) . '».';
        }
    }
    header('Location: pedidos.php' . (isset($_GET['estado']) ? '?estado=' . urlencode((string) $_GET['estado']) : ''));
    exit;
}

$filtro = $_GET['estado'] ?? '';
if ($filtro !== '' && !in_array($filtro, $estadosValidos, true)) {
    $filtro = '';
}

if ($filtro !== '') {
    $stmt = $mysqli->prepare(
        'SELECT id_pedido, nombre_cliente, telefono_cliente, direccion_entrega, detalle, estado, fecha
         FROM pedido
         WHERE id_comercio = ? AND estado = ?
         ORDER BY fecha DESC'
    );
    $stmt->bind_param('is', $idComercio, $filtro);
} else {
    $stmt = $mysqli->prepare(
        'SELECT id_pedido, nombre_cliente, telefono_cliente, direccion_entrega, detalle, estado, fecha
         FROM pedido
         WHERE id_comercio = ?
         ORDER BY FIELD(estado, \'nuevo\', \'confirmado\', \'entregado\', \'cancelado\'), fecha DESC'
    );
    $stmt->bind_param('i', $idComercio);
}
$stmt->execute();
$pedidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$itemsPorPedido = [];
if (count($pedidos) > 0) {
    $ids = array_map(static fn ($p) => (int) $p['id_pedido'], $pedidos);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    try {
        $sqlItems = "SELECT id_pedido, nombre, cantidad, precio_unitario
                     FROM pedido_item WHERE id_pedido IN ($placeholders) ORDER BY id_pedido_item";
        $stItems = $mysqli->prepare($sqlItems);
        $stItems->bind_param($types, ...$ids);
        $stItems->execute();
        foreach ($stItems->get_result()->fetch_all(MYSQLI_ASSOC) as $it) {
            $itemsPorPedido[(int) $it['id_pedido']][] = $it;
        }
    } catch (mysqli_sql_exception $e) {
        // Tabla aún no migrada
    }
}

$mensaje = $_SESSION['mensaje'] ?? null;
unset($_SESSION['mensaje']);

$tituloPagina = 'Pedidos · ' . ($_SESSION['comercio_local'] ?? 'Comercio');
require __DIR__ . '/../includes/header.php';
?>

<div class="contenedor">
    <div class="page-head">
        <div>
            <h1>Bandeja de pedidos</h1>
            <p><?= e($_SESSION['comercio_local'] ?? '') ?> · <?= count($pedidos) ?> visible(s).</p>
        </div>
        <a class="btn btn-line" href="index.php">← Panel</a>
    </div>

    <?php if ($mensaje): ?>
        <div class="alerta alerta-exito"><?= e($mensaje) ?></div>
    <?php endif; ?>

    <div class="filtros-bar">
        <a href="pedidos.php" class="<?= $filtro === '' ? 'activo' : '' ?>">Todos</a>
        <?php foreach ($estadosValidos as $est): ?>
            <a href="pedidos.php?estado=<?= e($est) ?>" class="<?= $filtro === $est ? 'activo' : '' ?>">
                <?= e(etiqueta_estado($est)) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (count($pedidos) === 0): ?>
        <div class="card"><p class="vacio">No hay pedidos con este filtro.</p></div>
    <?php else: ?>
        <div class="tabla-envoltorio">
            <table>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Celular</th>
                    <th>Dirección</th>
                    <th>Pedido</th>
                    <th>Estado</th>
                </tr>
                <?php foreach ($pedidos as $ped): ?>
                    <?php
                    $idPed = (int) $ped['id_pedido'];
                    $lineas = $itemsPorPedido[$idPed] ?? [];
                    ?>
                    <tr>
                        <td><?= $idPed ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($ped['fecha'])) ?></td>
                        <td><?= e($ped['nombre_cliente']) ?></td>
                        <td>
                            <a href="<?= e(enlaceWhatsApp($ped['telefono_cliente'], 'Hola ' . $ped['nombre_cliente'] . ', te escribimos de ' . ($_SESSION['comercio_local'] ?? 'VeciGo') . ' por tu pedido #' . $idPed)) ?>"
                               target="_blank" rel="noopener"><?= e($ped['telefono_cliente']) ?></a>
                        </td>
                        <td><?= e($ped['direccion_entrega']) ?></td>
                        <td>
                            <?php if (count($lineas) > 0): ?>
                                <ul class="lista-items">
                                    <?php foreach ($lineas as $it): ?>
                                        <li>
                                            <?= (int) $it['cantidad'] ?> × <?= e($it['nombre']) ?>
                                            <span class="muted"><?= formatearMoneda((float) $it['precio_unitario']) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <?= e($ped['detalle']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" action="pedidos.php<?= $filtro !== '' ? '?estado=' . e($filtro) : '' ?>"
                                  style="display:flex;flex-direction:column;gap:0.4rem;min-width:130px;">
                                <input type="hidden" name="id_pedido" value="<?= $idPed ?>">
                                <select name="estado" aria-label="Estado del pedido">
                                    <?php foreach ($estadosValidos as $est): ?>
                                        <option value="<?= e($est) ?>" <?= $ped['estado'] === $est ? 'selected' : '' ?>>
                                            <?= e(etiqueta_estado($est)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-coral" type="submit">Guardar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
