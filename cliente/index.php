<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../funciones.php';
require_once __DIR__ . '/../conexion.php';
exigir_cliente();
refrescar_sesion_cliente($mysqli);

$idCliente = cliente_id_sesion();
$telefono = (string) ($_SESSION['cliente_telefono'] ?? '');

$stmt = $mysqli->prepare(
    "SELECT
        (SELECT COUNT(*) FROM pedido WHERE id_usuario = ? OR (? <> '' AND telefono_cliente = ?)) AS total,
        (SELECT COUNT(*) FROM pedido WHERE (id_usuario = ? OR (? <> '' AND telefono_cliente = ?)) AND estado = 'nuevo') AS nuevos,
        (SELECT COUNT(*) FROM pedido WHERE (id_usuario = ? OR (? <> '' AND telefono_cliente = ?)) AND estado IN ('confirmado','nuevo')) AS activos"
);
$stmt->bind_param(
    'issississ',
    $idCliente, $telefono, $telefono,
    $idCliente, $telefono, $telefono,
    $idCliente, $telefono, $telefono
);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

$stmt = $mysqli->prepare(
    "SELECT p.id_pedido, p.detalle, p.estado, p.fecha, c.nombre AS comercio
     FROM pedido p
     JOIN comercio c ON c.id_comercio = p.id_comercio
     WHERE p.id_usuario = ? OR (? <> '' AND p.telefono_cliente = ?)
     ORDER BY p.fecha DESC
     LIMIT 5"
);
$stmt->bind_param('iss', $idCliente, $telefono, $telefono);
$stmt->execute();
$recientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$perfilOk = $_SESSION['cliente_telefono'] !== '' && $_SESSION['cliente_direccion'] !== '';

$tituloPagina = 'Mi cuenta · VeciGo';
require __DIR__ . '/../includes/header.php';
?>

<div class="contenedor">
    <div class="page-head">
        <div>
            <h1>Hola, <?= e($_SESSION['cliente_nombre'] ?? 'vecino') ?></h1>
            <p>Tu espacio para pedidos y datos de entrega.</p>
        </div>
        <div class="acciones-fila">
            <a class="btn btn-coral" href="<?= e(base_path()) ?>index.php#categorias">Pedir ahora</a>
            <a class="btn btn-line" href="perfil.php">Mi información</a>
        </div>
    </div>

    <?php if (!$perfilOk): ?>
        <div class="alerta alerta-error">
            Completa tu celular y dirección para pedir más rápido.
            <a href="perfil.php" style="margin-left:0.4rem;font-weight:700;">Ir al perfil →</a>
        </div>
    <?php endif; ?>

    <div class="panel-stats">
        <div class="stat"><strong><?= (int) ($stats['total'] ?? 0) ?></strong><span>pedidos</span></div>
        <div class="stat"><strong><?= (int) ($stats['nuevos'] ?? 0) ?></strong><span>nuevos</span></div>
        <div class="stat"><strong><?= (int) ($stats['activos'] ?? 0) ?></strong><span>en curso</span></div>
    </div>

    <div class="card">
        <div class="page-head" style="margin-bottom:1rem;">
            <h2 style="margin:0;">Últimos pedidos</h2>
            <a class="btn btn-sm btn-line" href="pedidos.php">Ver todos</a>
        </div>
        <?php if (count($recientes) === 0): ?>
            <p class="vacio">Aún no tienes pedidos. Explora los comercios del barrio.</p>
            <a class="btn btn-coral" href="<?= e(base_path()) ?>index.php#categorias">Ver categorías</a>
        <?php else: ?>
            <div class="tabla-envoltorio">
                <table>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Comercio</th>
                        <th>Detalle</th>
                        <th>Estado</th>
                    </tr>
                    <?php foreach ($recientes as $ped): ?>
                        <tr>
                            <td><?= (int) $ped['id_pedido'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($ped['fecha'])) ?></td>
                            <td><?= e($ped['comercio']) ?></td>
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
