<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../funciones.php';
require_once __DIR__ . '/../conexion.php';
exigir_comercio();

$idComercio = comercio_id_sesion();

$stmt = $mysqli->prepare(
    "SELECT
        (SELECT COUNT(*) FROM pedido WHERE id_comercio = ? AND estado = 'nuevo') AS nuevos,
        (SELECT COUNT(*) FROM pedido WHERE id_comercio = ? AND estado = 'confirmado') AS confirmados,
        (SELECT COUNT(*) FROM pedido WHERE id_comercio = ?) AS total,
        (SELECT COUNT(*) FROM producto WHERE id_comercio = ? AND disponible = 1) AS productos"
);
$stmt->bind_param('iiii', $idComercio, $idComercio, $idComercio, $idComercio);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

$stmt = $mysqli->prepare(
    "SELECT id_pedido, nombre_cliente, detalle, estado, fecha, telefono_cliente
     FROM pedido
     WHERE id_comercio = ?
     ORDER BY FIELD(estado, 'nuevo', 'confirmado', 'entregado', 'cancelado'), fecha DESC
     LIMIT 8"
);
$stmt->bind_param('i', $idComercio);
$stmt->execute();
$recientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$tituloPagina = 'Mi local · VeciGo';
require __DIR__ . '/../includes/header.php';
?>

<div class="contenedor">
    <div class="page-head">
        <div>
            <h1><?= e($_SESSION['comercio_local'] ?? 'Mi local') ?></h1>
            <p>Hola, <?= e($_SESSION['comercio_nombre'] ?? '') ?>. Pedidos y menú de tu comercio.</p>
        </div>
        <div class="acciones-fila">
            <a class="btn btn-coral" href="pedidos.php">Bandeja de pedidos</a>
            <a class="btn btn-line" href="productos.php">Mi menú</a>
            <a class="btn btn-line" href="perfil.php">Dirección / mapa</a>
        </div>
    </div>

    <div class="panel-stats">
        <div class="stat"><strong><?= (int) $stats['nuevos'] ?></strong><span>nuevos</span></div>
        <div class="stat"><strong><?= (int) $stats['confirmados'] ?></strong><span>confirmados</span></div>
        <div class="stat"><strong><?= (int) $stats['total'] ?></strong><span>pedidos</span></div>
        <div class="stat"><strong><?= (int) $stats['productos'] ?></strong><span>en menú</span></div>
    </div>

    <div class="card">
        <h2 style="margin-bottom:1rem;">Últimos pedidos de tu local</h2>
        <?php if (count($recientes) === 0): ?>
            <p class="vacio">Aún no hay pedidos para este comercio.</p>
        <?php else: ?>
            <div class="tabla-envoltorio">
                <table>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Detalle</th>
                        <th>Estado</th>
                    </tr>
                    <?php foreach ($recientes as $ped): ?>
                        <tr>
                            <td><?= (int) $ped['id_pedido'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($ped['fecha'])) ?></td>
                            <td><?= e($ped['nombre_cliente']) ?></td>
                            <td><?= e($ped['detalle']) ?></td>
                            <td>
                                <span class="badge badge-estado-<?= e($ped['estado']) ?>">
                                    <?= e(etiqueta_estado($ped['estado'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
