<?php
require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/conexion.php';

$q = trim($_GET['q'] ?? '');
$idCategoria = (int) ($_GET['categoria'] ?? 0);

enviarHeadersCache($q !== '' ? 'privado' : 'publico');

$categorias = obtenerCategorias($mysqli);
$comercios = obtenerLocalesMapa($mysqli, $idCategoria, $q);

$pins = [];
foreach ($comercios as $c) {
    $popup = '<div class="map-popup">'
        . '<strong>' . e($c['nombre']) . '</strong><br>'
        . '<span class="muted">' . e($c['categoria']) . '</span><br>'
        . '<small>' . e($c['direccion']) . '</small><br>'
        . '<a class="btn btn-coral btn-sm" href="comercio.php?id=' . (int) $c['id_comercio'] . '">Ver local</a> '
        . '<a class="btn btn-ws btn-sm" target="_blank" rel="noopener" href="'
        . e(enlaceWhatsApp($c['telefono'], 'Hola ' . $c['nombre'] . ', te escribo desde VeciGo'))
        . '">WhatsApp</a>'
        . '</div>';

    $pins[] = [
        'lat' => (float) $c['latitud'],
        'lng' => (float) $c['longitud'],
        'nombre' => $c['nombre'],
        'popupHtml' => $popup,
    ];
}

$queryLista = http_build_query(array_filter([
    'q' => $q !== '' ? $q : null,
    'categoria' => $idCategoria > 0 ? $idCategoria : null,
]));

$tituloPagina = 'Mapa de locales · VeciGo';

require __DIR__ . '/includes/header.php';
?>

<div class="contenedor map-page">
    <div class="page-head">
        <div>
            <h1>Mapa del barrio</h1>
            <p>
                <?= count($pins) ?> local(es) con ubicación en Bogotá · toca un pin para ver el local
            </p>
        </div>
        <div class="view-toggle" role="navigation" aria-label="Vista de locales">
            <a class="view-toggle-btn" href="locales.php<?= $queryLista !== '' ? '?' . e($queryLista) : '' ?>">Lista</a>
            <a class="view-toggle-btn is-active" href="locales_mapa.php<?= $queryLista !== '' ? '?' . e($queryLista) : '' ?>" aria-current="page">Mapa</a>
        </div>
    </div>

    <form class="filtros-locales" method="get" action="locales_mapa.php" role="search">
        <div class="campo">
            <label for="q">Buscar</label>
            <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Nombre o dirección…">
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

    <?php if (count($pins) === 0): ?>
        <div class="card">
            <p class="vacio">No hay locales con coordenadas. El admin/comercio debe cargar latitud y longitud en «Mi local».</p>
        </div>
    <?php else: ?>
        <div id="mapaLocales" class="mapa-locales" style="width:100%;height:480px;min-height:480px" role="region" aria-label="Mapa de comercios"></div>
        <p class="mapa-error" data-map-error hidden></p>
    <?php endif; ?>
</div>

<?php if (count($pins) > 0): ?>
<script src="<?= e(asset('js/mapa.js')) ?>"></script>
<script>
if (typeof VecigoMap !== 'undefined') {
  VecigoMap.init('mapaLocales', <?= json_encode($pins, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>, {
    zoom: 13,
    center: [4.6097, -74.0817]
  });
}
</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
