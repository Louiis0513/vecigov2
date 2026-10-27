<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/conexion.php';

$q = trim($_GET['q'] ?? '');
$idCategoria = (int) ($_GET['categoria'] ?? 0);

enviarHeadersCache($q !== '' ? 'privado' : 'publico');

$categorias = obtenerCategorias($mysqli);
$comercios = obtenerLocales($mysqli, $idCategoria, $q);

$queryMapa = http_build_query(array_filter([
    'q' => $q !== '' ? $q : null,
    'categoria' => $idCategoria > 0 ? $idCategoria : null,
]));

$tituloPagina = 'Locales del barrio · VeciGo';
require __DIR__ . '/includes/header.php';
?>

<div class="contenedor">
    <div class="page-head">
        <div>
            <h1>Locales del barrio</h1>
            <p><?= count($comercios) ?> comercio(s) · lista o mapa</p>
        </div>
        <div class="view-toggle" role="navigation" aria-label="Vista de locales">
            <a class="view-toggle-btn is-active" href="locales.php<?= $queryMapa !== '' ? '?' . e($queryMapa) : '' ?>" aria-current="page">Lista</a>
            <a class="view-toggle-btn" href="locales_mapa.php<?= $queryMapa !== '' ? '?' . e($queryMapa) : '' ?>">Mapa</a>
        </div>
    </div>

    <form class="filtros-locales" method="get" action="locales.php" role="search">
        <div class="campo">
            <label for="q">Buscar</label>
            <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Nombre, dirección, categoría…">
        </div>
        <div class="campo">
            <label for="categoria">Categoría</label>
            <select id="categoria" name="categoria">
                <option value="0">Todas</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= (int) $cat['id_categoria_comercio'] ?>"
                        <?= $idCategoria === (int) $cat['id_categoria_comercio'] ? 'selected' : '' ?>>
                        <?= e($cat['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-coral" type="submit">Filtrar</button>
    </form>

    <?php if (count($comercios) === 0): ?>
        <div class="card"><p class="vacio">No hay locales con ese filtro.</p></div>
    <?php else: ?>
        <div class="grid-comercios">
            <?php foreach ($comercios as $com): ?>
                <article class="com-card">
                    <div class="com-card__foto">
                        <img src="<?= e(foto_url($com['foto_url'])) ?>" alt="<?= e($com['nombre']) ?>" loading="lazy">
                    </div>
                    <div class="com-card__body">
                        <span class="badge"><?= e($com['categoria']) ?></span>
                        <h3><?= e($com['nombre']) ?></h3>
                        <p class="muted" style="font-size:0.9rem;"><?= e($com['direccion']) ?></p>
                        <p style="color:var(--muted);font-size:0.88rem;"><?= e($com['horario'] ?? '') ?></p>
                        <div class="acciones-fila">
                            <a class="btn btn-coral btn-sm" href="comercio.php?id=<?= (int) $com['id_comercio'] ?>">Ver local</a>
                            <a class="btn btn-ws btn-sm" target="_blank" rel="noopener"
                               href="<?= e(enlaceWhatsApp($com['telefono'], 'Hola ' . $com['nombre'] . ', te escribo desde VeciGo')) ?>">WhatsApp</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
