<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/conexion.php';

$tablas = ['categoria_comercio', 'comercio', 'producto', 'pedido', 'usuario'];
$conteos = [];

foreach ($tablas as $tabla) {
    $resultado = $mysqli->query("SELECT COUNT(*) AS total FROM `$tabla`");
    $conteos[$tabla] = $resultado->fetch_assoc()['total'];
}

$tituloPagina = 'Estado · VeciGo';
require __DIR__ . '/includes/header.php';
?>

<div class="contenedor">
    <div class="alerta alerta-exito">Conexión exitosa a la base de datos <strong>vecigo</strong></div>

    <div class="tabla-envoltorio">
        <table>
            <tr><th>Tabla</th><th>Registros</th></tr>
            <?php foreach ($conteos as $tabla => $total): ?>
                <tr>
                    <td><?= e($tabla) ?></td>
                    <td><?= (int) $total ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <p style="margin-top: 1.25rem; display:flex; gap:0.6rem; flex-wrap:wrap;">
        <a class="btn btn-coral" href="index.php">Ir a VeciGo</a>
        <a class="btn btn-line" href="login.php">Entrar</a>
    </p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
