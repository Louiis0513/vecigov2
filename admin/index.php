<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../funciones.php';
require_once __DIR__ . '/../conexion.php';
exigir_admin();

$stats = [
    'categorias' => (int) $mysqli->query('SELECT COUNT(*) AS n FROM categoria_comercio')->fetch_assoc()['n'],
    'comercios'  => (int) $mysqli->query('SELECT COUNT(*) AS n FROM comercio')->fetch_assoc()['n'],
    'productos'  => (int) $mysqli->query('SELECT COUNT(*) AS n FROM producto')->fetch_assoc()['n'],
    'pedidos'    => (int) $mysqli->query('SELECT COUNT(*) AS n FROM pedido')->fetch_assoc()['n'],
    'nuevos'     => (int) $mysqli->query("SELECT COUNT(*) AS n FROM pedido WHERE estado = 'nuevo'")->fetch_assoc()['n'],
];

$recientes = $mysqli->query(
    "SELECT p.id_pedido, p.nombre_cliente, p.detalle, p.estado, p.fecha, c.nombre AS comercio
     FROM pedido p
     JOIN comercio c ON c.id_comercio = p.id_comercio
     ORDER BY p.fecha DESC
     LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

$tituloPagina = 'Panel · VeciGo';
require __DIR__ . '/../includes/header.php';
?>

<div class="contenedor">
    <div class="page-head">
        <div>
            <h1>Hola, <?= e($_SESSION['admin_nombre'] ?? 'Admin') ?></h1>
            <p>Resumen del barrio y pedidos recientes.</p>
        </div>
        <a class="btn btn-coral" href="pedidos.php">Ver todos los pedidos</a>
    </div>

    <div class="panel-stats">
        <div class="stat"><strong><?= $stats['categorias'] ?></strong><span>categorías</span></div>
        <div class="stat"><strong><?= $stats['comercios'] ?></strong><span>comercios</span></div>
        <div class="stat"><strong><?= $stats['productos'] ?></strong><span>productos</span></div>
        <div class="stat"><strong><?= $stats['pedidos'] ?></strong><span>pedidos</span></div>
        <div class="stat"><strong><?= $stats['nuevos'] ?></strong><span>nuevos</span></div>
    </div>

    <div class="card">
        <h2 style="margin-bottom:1rem;">Últimos pedidos</h2>
        <?php if (count($recientes) === 0): ?>
            <p class="vacio">Aún no hay pedidos.</p>
        <?php else: ?>
            <div class="tabla-envoltorio">
                <table>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Comercio</th>
                        <th>Cliente</th>
                        <th>Detalle</th>
                        <th>Estado</th>
                    </tr>
                    <?php foreach ($recientes as $ped): ?>
                        <tr>
                            <td><?= (int) $ped['id_pedido'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($ped['fecha'])) ?></td>
                            <td><?= e($ped['comercio']) ?></td>
                            <td><?= e($ped['nombre_cliente']) ?></td>
                            <td><?= e($ped['detalle']) ?></td>
                            <td><span class="badge badge-estado-<?= e($ped['estado']) ?>"><?= e(etiqueta_estado($ped['estado'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
