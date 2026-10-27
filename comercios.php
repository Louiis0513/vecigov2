<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/conexion.php';

$idCategoria = (int) ($_GET['categoria'] ?? 0);
$categoria = obtenerCategoria($mysqli, $idCategoria);

if ($categoria === null) {
    enviarHeadersCache('privado');
    http_response_code(404);
    $tituloPagina = 'Categoría no encontrada · VeciGo';
    require __DIR__ . '/includes/header.php';
    echo '<div class="contenedor"><div class="alerta alerta-error">Categoría no encontrada.</div>';
    echo '<a class="btn btn-coral" href="index.php">Volver al inicio</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

enviarHeadersCache('publico');
$comercios = obtenerLocales($mysqli, $idCategoria);
$todas = obtenerCategorias($mysqli);

$tituloPagina = $categoria['nombre'] . ' · VeciGo';
require __DIR__ . '/includes/header.php';
?>

<div class="contenedor">
    <a class="volver" href="index.php#categorias">← Volver a categorías</a>

    <div class="page-head">
        <div>
            <h1><?= e($categoria['nombre']) ?></h1>
            <p><?= count($comercios) ?> comercio<?= count($comercios) === 1 ? '' : 's' ?> listo<?= count($comercios) === 1 ? '' : 's' ?> para pedir en el barrio.</p>
        </div>
    </div>

    <div class="filtros-bar">
        <?php foreach ($todas as $cat): ?>
            <a href="comercios.php?categoria=<?= (int) $cat['id_categoria_comercio'] ?>"
               class="<?= $idCategoria === (int) $cat['id_categoria_comercio'] ? 'activo' : '' ?>">
                <?= e($cat['nombre']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (count($comercios) === 0): ?>
        <div class="card"><p class="vacio">Aún no hay comercios en esta categoría.</p></div>
    <?php else: ?>
        <div class="grid-comercios">
            <?php foreach ($comercios as $com): ?>
                <article class="com-card">
                    <div class="com-card__foto">
                        <img src="<?= e(foto_url($com['foto_url'])) ?>" alt="<?= e($com['nombre']) ?>" loading="lazy">
                    </div>
                    <div class="com-card__body">
                        <span class="badge"><?= e($categoria['nombre']) ?></span>
                        <h3><?= e($com['nombre']) ?></h3>
                        <p style="color:var(--muted);font-size:0.92rem;">
                            <?= e($com['descripcion'] ?: $com['direccion']) ?>
                        </p>
                        <p style="font-size:0.85rem;color:var(--muted);"><?= e($com['horario'] ?? '') ?></p>
                        <div class="acciones-fila">
                            <a href="comercio.php?id=<?= (int) $com['id_comercio'] ?>" class="btn btn-coral btn-sm">Ver catálogo</a>
                            <a href="<?= e(enlaceWhatsApp($com['telefono'], 'Hola ' . $com['nombre'] . ', te escribo desde VeciGo')) ?>"
                               target="_blank" rel="noopener" class="btn btn-ws btn-sm">WhatsApp</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
