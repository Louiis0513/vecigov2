<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/conexion.php';
iniciar_sesion();

$idComercio = (int) ($_GET['id'] ?? 0);
$comercio = obtenerComercioActivo($mysqli, $idComercio);

if ($comercio === null) {
    enviarHeadersCache('privado');
    http_response_code(404);
    $tituloPagina = 'Comercio no encontrado · VeciGo';
    require __DIR__ . '/includes/header.php';
    echo '<div class="contenedor"><div class="alerta alerta-error">Comercio no encontrado.</div>';
    echo '<a class="btn btn-coral" href="locales.php">Ver locales</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

enviarHeadersCache(cliente_logueado() ? 'privado' : 'publico');
$productos = obtenerProductosComercio($mysqli, $idComercio);

$esCliente = cliente_logueado();
$enCarrito = $esCliente && carrito_id_comercio() === $idComercio ? carrito_estado()['items'] : [];
$cantCarrito = $esCliente ? carrito_cantidad_total() : 0;
$mensaje = $_SESSION['mensaje'] ?? null;
unset($_SESSION['mensaje']);

$lat = $comercio['latitud'] !== null && $comercio['latitud'] !== '' ? (float) $comercio['latitud'] : 0.0;
$lng = $comercio['longitud'] !== null && $comercio['longitud'] !== '' ? (float) $comercio['longitud'] : 0.0;
$tieneMapa = $lat !== 0.0 && $lng !== 0.0;

$tituloPagina = $comercio['nombre'] . ' · VeciGo';
require __DIR__ . '/includes/header.php';
?>

<div class="contenedor">
    <a class="volver" href="locales.php">← Locales del barrio</a>

    <?php if ($mensaje && $esCliente): ?>
        <div class="alerta alerta-exito">
            <?= e($mensaje) ?>
            <a href="carrito.php" style="margin-left:0.5rem;font-weight:700;">Ver carrito →</a>
        </div>
    <?php endif; ?>

    <section class="local-layout">
        <div class="local-info card">
            <div class="local-info__foto">
                <img src="<?= e(foto_url($comercio['foto_url'])) ?>" alt="<?= e($comercio['nombre']) ?>">
            </div>
            <span class="badge"><?= e($comercio['categoria']) ?></span>
            <h1><?= e($comercio['nombre']) ?></h1>
            <p style="color:var(--muted);"><?= e($comercio['descripcion'] ?? '') ?></p>
            <ul class="meta-list">
                <li><strong>Dirección:</strong> <?= e($comercio['direccion']) ?></li>
                <li><strong>Horario:</strong> <?= e($comercio['horario'] ?? 'Consultar') ?></li>
                <li><strong>Celular:</strong> <?= e($comercio['telefono']) ?></li>
            </ul>
            <div class="acciones-fila">
                <a class="btn btn-ws" target="_blank" rel="noopener"
                   href="<?= e(enlaceWhatsApp($comercio['telefono'], 'Hola ' . $comercio['nombre'] . ', te escribo desde VeciGo')) ?>">
                    WhatsApp
                </a>
                <a class="btn btn-coral" href="#catalogo">Ver productos</a>
            </div>
        </div>

        <div class="local-mapa card">
            <?php if ($tieneMapa): ?>
                <div id="mapaLocal" class="mapa-local" style="width:100%;height:300px;min-height:300px" role="region" aria-label="Ubicación del comercio"></div>
                <p class="muted" style="margin-top:0.75rem;font-size:0.88rem;">
                    Ubicación aproximada · <?= e($comercio['direccion']) ?>
                </p>
                <p class="mapa-error" data-map-error hidden></p>
            <?php else: ?>
                <div class="mapa-placeholder">
                    <p>Este local aún no cargó coordenadas en el mapa.</p>
                    <p class="muted"><?= e($comercio['direccion']) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="bloque__title" id="catalogo">
        <h2>Productos</h2>
        <p>
            <?php if ($esCliente): ?>
                Agrega al carrito y finaliza cuando quieras.
            <?php else: ?>
                <a href="login.php">Entra como cliente</a> para armar tu carrito y pedir.
            <?php endif; ?>
        </p>
    </div>

    <?php if (count($productos) === 0): ?>
        <div class="card"><p class="vacio">Este comercio aún no tiene productos.</p></div>
    <?php else: ?>
        <div class="grid-productos">
            <?php foreach ($productos as $prod): ?>
                <?php $qty = (int) ($enCarrito[(int) $prod['id_producto']] ?? 0); ?>
                <article class="prod-card <?= $prod['disponible'] ? '' : 'agotado' ?>">
                    <div class="prod-card__foto">
                        <img src="<?= e(foto_url($prod['foto_url'], 'img/hero/barrio.jpg')) ?>"
                             alt="<?= e($prod['nombre']) ?>" loading="lazy">
                    </div>
                    <div class="prod-card__body">
                        <?php if ($prod['disponible']): ?>
                            <span class="badge badge-ok">Disponible</span>
                        <?php else: ?>
                            <span class="badge badge-alerta">Agotado</span>
                        <?php endif; ?>
                        <h3><?= e($prod['nombre']) ?></h3>
                        <p style="color:var(--muted);font-size:0.9rem;"><?= e($prod['descripcion'] ?? '') ?></p>
                        <p class="precio"><?= formatearMoneda((float) $prod['precio']) ?></p>
                        <?php if ($prod['disponible'] && $esCliente): ?>
                            <form method="post" action="carrito.php" class="prod-add-form">
                                <input type="hidden" name="accion" value="agregar">
                                <input type="hidden" name="id_comercio" value="<?= (int) $idComercio ?>">
                                <input type="hidden" name="id_producto" value="<?= (int) $prod['id_producto'] ?>">
                                <input type="hidden" name="cantidad" value="1">
                                <button type="submit" class="btn btn-line btn-sm">
                                    <?= $qty > 0 ? 'Agregar otro (' . $qty . ')' : '+ Agregar al carrito' ?>
                                </button>
                            </form>
                        <?php elseif ($prod['disponible']): ?>
                            <a class="btn btn-line btn-sm" href="login.php">Entrar para pedir</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($cantCarrito > 0): ?>
    <div class="cart-dock">
        <div class="cart-dock__inner">
            <div>
                <strong><?= $cantCarrito ?> en el carrito</strong>
                <span class="muted">
                    <?php if (carrito_id_comercio() === $idComercio): ?>
                        de este local
                    <?php else: ?>
                        de otro local
                    <?php endif; ?>
                </span>
            </div>
            <a class="btn btn-coral" href="carrito.php">Ver carrito</a>
        </div>
    </div>
<?php endif; ?>

<?php if ($tieneMapa): ?>
<script src="<?= e(asset('js/mapa.js')) ?>"></script>
<script>
  VecigoMap.init('mapaLocal', [{
    lat: <?= json_encode($lat) ?>,
    lng: <?= json_encode($lng) ?>,
    nombre: <?= json_encode($comercio['nombre'], JSON_UNESCAPED_UNICODE) ?>,
    popupHtml: <?= json_encode(
        '<strong>' . e($comercio['nombre']) . '</strong><br><small>' . e($comercio['direccion']) . '</small>',
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
    ) ?>
  }], { zoom: 16, openFirst: true });
</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
