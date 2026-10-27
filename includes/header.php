<?php
/** @var string $tituloPagina */
/** @var string|null $bodyClass */
$tituloPagina = $tituloPagina ?? 'VeciGo';
$bodyClass = $bodyClass ?? '';
$base = base_path();
iniciar_sesion();

$ctx = nav_contexto();
$script = $_SERVER['SCRIPT_NAME'] ?? '';
$esHomePublica = str_ends_with($script, '/index.php')
    && !str_contains($script, '/admin/')
    && !str_contains($script, '/comercio/')
    && !str_contains($script, '/cliente/');
$nCarrito = carrito_cantidad_total();

if ($ctx === 'comercio') {
    $brandHref = $base . 'comercio/index.php';
    $brandSub = $_SESSION['comercio_local'] ?? 'Panel local';
} elseif ($ctx === 'admin') {
    $brandHref = $base . 'admin/index.php';
    $brandSub = 'Admin del barrio';
} elseif ($ctx === 'cliente') {
    $brandHref = $base . 'cliente/index.php';
    $brandSub = $_SESSION['cliente_nombre'] ?? 'Mi cuenta';
} else {
    $brandHref = $base . 'index.php';
    $brandSub = 'Tu barrio pide juntos';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="VeciGo — pide a los comercios de tu barrio: menú, domicilios y WhatsApp.">
    <title><?= e($tituloPagina) ?></title>
    <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('estilos.css')) ?>?v=<?= e(cssVersion()) ?>">
</head>
<body class="<?= e($bodyClass) ?> ctx-<?= e($ctx) ?>">
<header class="site-header">
    <div class="site-header__inner">
        <a class="brand" href="<?= e($brandHref) ?>" aria-label="VeciGo">
            <span class="brand__mark" aria-hidden="true">V</span>
            <span class="brand__text">
                <strong>VeciGo</strong>
                <small><?= e($brandSub) ?></small>
            </span>
        </a>

        <button type="button" class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="siteNav" aria-label="Abrir menú">
            <span></span><span></span><span></span>
        </button>

        <nav class="site-nav" id="siteNav" data-ctx="<?= e($ctx) ?>" aria-label="Principal">
            <?php if ($ctx === 'publico'): ?>
                <div class="nav-group nav-group--links">
                    <a class="nav-link<?= $esHomePublica ? ' is-active' : '' ?>" href="<?= e($base) ?>index.php">Inicio</a>
                    <a class="nav-link<?= nav_activo('/locales.php', '/locales_mapa.php') ?>" href="<?= e($base) ?>locales.php">Locales</a>
                    <a class="nav-link<?= nav_activo('/registro.php') ?>" href="<?= e($base) ?>registro.php">Crear cuenta</a>
                    <a class="nav-link<?= nav_activo('/registro_comercio.php') ?>" href="<?= e($base) ?>registro_comercio.php">Registrar local</a>
                </div>
                <div class="nav-group nav-group--actions">
                    <a class="nav-cta<?= nav_activo('/login.php') ?>" href="<?= e($base) ?>login.php">Entrar</a>
                </div>

            <?php elseif ($ctx === 'cliente'): ?>
                <div class="nav-group nav-group--mode">
                    <span class="nav-pill nav-pill--cliente">Cliente</span>
                </div>
                <div class="nav-group nav-group--links">
                    <a class="nav-link" href="<?= e($base) ?>locales.php">Locales</a>
                    <a class="nav-link<?= nav_activo('/locales_mapa.php') ?>" href="<?= e($base) ?>locales_mapa.php">Mapa</a>
                    <a class="nav-link<?= nav_activo('/carrito.php') ?>" href="<?= e($base) ?>carrito.php">
                        Carrito<?= $nCarrito > 0 ? ' (' . $nCarrito . ')' : '' ?>
                    </a>
                    <a class="nav-link<?= nav_activo('/cliente/pedidos.php') ?>" href="<?= e($base) ?>cliente/pedidos.php">Pedidos</a>
                    <a class="nav-link<?= nav_activo('/cliente/perfil.php', '/cliente/index.php') ?>" href="<?= e($base) ?>cliente/index.php">Cuenta</a>
                </div>
                <div class="nav-group nav-group--actions">
                    <a class="nav-cta nav-cta--ghost" href="<?= e($base) ?>logout.php">Salir</a>
                </div>

            <?php elseif ($ctx === 'comercio'): ?>
                <div class="nav-group nav-group--mode">
                    <span class="nav-pill">Comercio</span>
                </div>
                <div class="nav-group nav-group--links">
                    <a class="nav-link<?= nav_activo('/comercio/index.php') ?>" href="<?= e($base) ?>comercio/index.php">Resumen</a>
                    <a class="nav-link<?= nav_activo('/comercio/pedidos.php') ?>" href="<?= e($base) ?>comercio/pedidos.php">Pedidos</a>
                    <a class="nav-link<?= nav_activo('/comercio/productos.php') ?>" href="<?= e($base) ?>comercio/productos.php">Menú</a>
                    <a class="nav-link<?= nav_activo('/comercio/perfil.php') ?>" href="<?= e($base) ?>comercio/perfil.php">Mi local</a>
                </div>
                <div class="nav-group nav-group--actions">
                    <a class="nav-cta nav-cta--ghost" href="<?= e($base) ?>locales_mapa.php">Ver mapa</a>
                    <a class="nav-cta nav-cta--ghost" href="<?= e($base) ?>logout.php">Salir</a>
                </div>

            <?php else: /* admin */ ?>
                <div class="nav-group nav-group--mode">
                    <span class="nav-pill nav-pill--admin">Admin</span>
                </div>
                <div class="nav-group nav-group--links">
                    <a class="nav-link<?= nav_activo('/admin/index.php') ?>" href="<?= e($base) ?>admin/index.php">Resumen</a>
                    <a class="nav-link<?= nav_activo('/admin/pedidos.php') ?>" href="<?= e($base) ?>admin/pedidos.php">Pedidos</a>
                </div>
                <div class="nav-group nav-group--actions">
                    <a class="nav-cta nav-cta--ghost" href="<?= e($base) ?>index.php">Ver barrio</a>
                    <a class="nav-cta nav-cta--ghost" href="<?= e($base) ?>logout.php">Salir</a>
                </div>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="site-main">
