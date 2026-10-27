<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/conexion.php';

enviarHeadersCache('publico');

$categorias = obtenerCategoriasHome($mysqli);
$totales = obtenerTotalesHome($mysqli);
$destacados = obtenerDestacados($mysqli);

$tituloPagina = 'VeciGo · Tu barrio pide juntos';
$bodyClass = 'home';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div>
        <p class="hero__eyebrow">Barrio · Pedidos locales</p>
        <h1>Tu barrio, <em>a un clic</em> de distancia.</h1>
        <p class="lead">
            Explora restaurantes, panaderías, droguerías y más. Mira el catálogo,
            arma tu pedido y el comercio te confirma por WhatsApp.
        </p>
        <div class="hero__actions">
            <a class="btn btn-coral" href="#categorias">Ver categorías</a>
            <?php if (cliente_logueado()): ?>
                <a class="btn btn-line" href="cliente/pedidos.php">Mis pedidos</a>
                <a class="btn btn-line" href="cliente/index.php">Mi cuenta</a>
            <?php else: ?>
                <a class="btn btn-line" href="registro.php">Crear cuenta</a>
                <a class="btn btn-line" href="login.php">Entrar</a>
            <?php endif; ?>
        </div>
    </div>
    <figure class="hero__visual">
        <img src="<?= e(asset('img/hero/barrio.jpg')) ?>" alt="Comercio del barrio" width="800" height="600">
        <div class="hero__chip">
            <strong>Sin apps de domicilio</strong>
            Habla directo con el vecino del local.
        </div>
    </figure>
</section>

<section class="stats" aria-label="Resumen del barrio">
    <div class="stat">
        <strong><?= $totales['comercios'] ?></strong>
        <span>comercios activos</span>
    </div>
    <div class="stat">
        <strong><?= $totales['productos'] ?></strong>
        <span>productos disponibles</span>
    </div>
    <div class="stat">
        <strong><?= $totales['pedidos'] ?></strong>
        <span>pedidos de ejemplo</span>
    </div>
</section>

<section class="bloque" id="categorias">
    <div class="bloque__title">
        <h2>¿Qué necesitas hoy?</h2>
        <p>Toca una categoría y entra al catálogo de los locales del barrio.</p>
    </div>
    <?php if (count($categorias) === 0): ?>
        <p class="vacio">Todavía no hay categorías. Importa <code>bd/vecigo.sql</code>.</p>
    <?php else: ?>
        <div class="cat-grid">
            <?php foreach ($categorias as $cat): ?>
                <a class="cat-card" href="comercios.php?categoria=<?= (int) $cat['id_categoria_comercio'] ?>">
                    <img src="<?= e(foto_url($cat['foto_url'], 'img/hero/barrio.jpg')) ?>"
                         alt="<?= e($cat['nombre']) ?>" loading="lazy" width="400" height="300">
                    <div class="cat-card__overlay">
                        <strong><?= e($cat['nombre']) ?></strong>
                        <span><?= (int) $cat['total'] ?> <?= (int) $cat['total'] === 1 ? 'comercio' : 'comercios' ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="bloque" id="locales">
    <div class="bloque__title bloque__title--row">
        <div>
            <h2>Locales del barrio</h2>
            <p>Los 3 más pedidos. Mira todos en lista o mapa.</p>
        </div>
        <a class="btn btn-line btn-sm" href="locales.php">Ver todos →</a>
    </div>
    <div class="grid-comercios grid-comercios--3">
        <?php foreach ($destacados as $com): ?>
            <article class="com-card">
                <div class="com-card__foto">
                    <img src="<?= e(foto_url($com['foto_url'])) ?>" alt="<?= e($com['nombre']) ?>" loading="lazy">
                </div>
                <div class="com-card__body">
                    <span class="badge"><?= e($com['categoria']) ?></span>
                    <h3><?= e($com['nombre']) ?></h3>
                    <p class="muted" style="font-size:0.9rem;"><?= e($com['direccion']) ?></p>
                    <p style="color:var(--muted);font-size:0.88rem;"><?= e($com['descripcion'] ?? '') ?></p>
                    <div class="acciones-fila">
                        <a class="btn btn-coral btn-sm" href="comercio.php?id=<?= (int) $com['id_comercio'] ?>">Ver local</a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <p style="margin-top:1.25rem;display:flex;gap:0.6rem;flex-wrap:wrap;">
        <a class="btn btn-coral" href="locales.php">Lista de locales</a>
        <a class="btn btn-line" href="locales_mapa.php">Mapa del barrio</a>
    </p>
</section>

<section class="bloque">
    <div class="bloque__title">
        <h2>Así funciona</h2>
        <p>Tres pasos. Sin cuentas obligatorias para pedir.</p>
    </div>
    <div class="pasos">
        <article class="paso" data-paso="1">
            <h3>Eliges</h3>
            <p>Entras a una categoría y ves los comercios del barrio.</p>
        </article>
        <article class="paso" data-paso="2">
            <h3>Armas el carrito</h3>
            <p>Agregas productos del local y revisas el carrito.</p>
        </article>
        <article class="paso" data-paso="3">
            <h3>Confirmas</h3>
            <p>Envías el pedido. El comercio te confirma por WhatsApp.</p>
        </article>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
