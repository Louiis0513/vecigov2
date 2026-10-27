<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../funciones.php';
require_once __DIR__ . '/../conexion.php';
exigir_admin();

$estadosValidos = ['nuevo', 'confirmado', 'entregado', 'cancelado'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPedido = (int) ($_POST['id_pedido'] ?? 0);
    $estado = $_POST['estado'] ?? '';
    if ($idPedido > 0 && in_array($estado, $estadosValidos, true)) {
        $stmt = $mysqli->prepare('UPDATE pedido SET estado = ? WHERE id_pedido = ?');
        $stmt->bind_param('si', $estado, $idPedido);
        $stmt->execute();
        $_SESSION['mensaje'] = 'Pedido #' . $idPedido . ' actualizado a «' . etiqueta_estado($estado) . '».';
    }
    header('Location: pedidos.php');
    exit;
}

$filtro = $_GET['estado'] ?? '';
if ($filtro !== '' && !in_array($filtro, $estadosValidos, true)) {
    $filtro = '';
}

if ($filtro !== '') {
    $stmt = $mysqli->prepare(
        'SELECT p.id_pedido, p.nombre_cliente, p.telefono_cliente, p.direccion_entrega,
                p.detalle, p.estado, p.fecha, c.nombre AS comercio, c.telefono AS tel_comercio
         FROM pedido p
         JOIN comercio c ON c.id_comercio = p.id_comercio
         WHERE p.estado = ?
         ORDER BY p.fecha DESC'
    );
    $stmt->bind_param('s', $filtro);
    $stmt->execute();
    $pedidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $pedidos = $mysqli->query(
        'SELECT p.id_pedido, p.nombre_cliente, p.telefono_cliente, p.direccion_entrega,
                p.detalle, p.estado, p.fecha, c.nombre AS comercio, c.telefono AS tel_comercio
         FROM pedido p
         JOIN comercio c ON c.id_comercio = p.id_comercio
         ORDER BY p.fecha DESC'
    )->fetch_all(MYSQLI_ASSOC);
}

$mensaje = $_SESSION['mensaje'] ?? null;
unset($_SESSION['mensaje']);

$tituloPagina = 'Pedidos · VeciGo';
require __DIR__ . '/../includes/header.php';
?>

<div class="contenedor">
    <div class="page-head">
        <div>
            <h1>Bandeja de pedidos</h1>
            <p>Total visible: <?= count($pedidos) ?> pedido(s).</p>
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
                    <th>Comercio</th>
                    <th>Cliente</th>
                    <th>Celular</th>
                    <th>Dirección</th>
                    <th>Pedido</th>
                    <th>Estado</th>
                </tr>
                <?php foreach ($pedidos as $ped): ?>
                    <tr>
                        <td><?= (int) $ped['id_pedido'] ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($ped['fecha'])) ?></td>
                        <td>
                            <?= e($ped['comercio']) ?>
                            <br>
                            <a class="btn btn-ws btn-sm" style="margin-top:0.4rem;" target="_blank" rel="noopener"
                               href="<?= e(enlaceWhatsApp($ped['tel_comercio'], 'Pedido #' . $ped['id_pedido'] . ' de ' . $ped['nombre_cliente'] . ': ' . $ped['detalle'])) ?>">
                                Avisar
                            </a>
                        </td>
                        <td><?= e($ped['nombre_cliente']) ?></td>
                        <td>
                            <a href="<?= e(enlaceWhatsApp($ped['telefono_cliente'], 'Hola ' . $ped['nombre_cliente'] . ', te escribimos por tu pedido VeciGo #' . $ped['id_pedido'])) ?>"
                               target="_blank" rel="noopener"><?= e($ped['telefono_cliente']) ?></a>
                        </td>
                        <td><?= e($ped['direccion_entrega']) ?></td>
                        <td><?= e($ped['detalle']) ?></td>
                        <td>
                            <form method="post" action="pedidos.php" style="display:flex;flex-direction:column;gap:0.4rem;min-width:130px;">
                                <input type="hidden" name="id_pedido" value="<?= (int) $ped['id_pedido'] ?>">
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
