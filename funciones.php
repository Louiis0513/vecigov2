<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/cache.php';

/**
 * Escapa texto para HTML.
 */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Moneda COP sin decimales: "$ 15.000".
 */
function formatearMoneda(float $valor): string
{
    return '$ ' . number_format($valor, 0, ',', '.');
}

/**
 * Enlace WhatsApp Colombia.
 */
function enlaceWhatsApp(string $telefono, string $mensaje): string
{
    $tel = preg_replace('/\D+/', '', $telefono) ?? '';
    return 'https://wa.me/57' . $tel . '?text=' . urlencode($mensaje);
}

/**
 * Ruta base relativa según profundidad (raíz, admin/ o comercio/).
 */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $base = (
        str_contains($script, '/admin/')
        || str_contains($script, '/comercio/')
        || str_contains($script, '/cliente/')
    ) ? '../' : '';
    return $base;
}

/**
 * URL de asset relativa a la raíz del proyecto.
 */
function asset(string $ruta): string
{
    $ruta = ltrim($ruta, '/');
    return base_path() . $ruta;
}

/**
 * Foto con fallback.
 */
function foto_url(?string $ruta, string $fallback = 'img/hero/barrio.jpg'): string
{
    if ($ruta === null || $ruta === '') {
        return asset($fallback);
    }
    $fs = __DIR__ . '/' . ltrim($ruta, '/');
    if (!is_file($fs)) {
        return asset($fallback);
    }
    return asset($ruta);
}

function iniciar_sesion(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function admin_logueado(): bool
{
    iniciar_sesion();
    return !empty($_SESSION['admin_id']);
}

function exigir_admin(): void
{
    if (!admin_logueado()) {
        header('Location: ' . base_path() . 'login.php');
        exit;
    }
}

function comercio_logueado(): bool
{
    iniciar_sesion();
    return !empty($_SESSION['comercio_user_id']) && !empty($_SESSION['comercio_id']);
}

function exigir_comercio(): void
{
    if (!comercio_logueado()) {
        header('Location: ' . base_path() . 'login.php');
        exit;
    }
}

function comercio_id_sesion(): int
{
    return (int) ($_SESSION['comercio_id'] ?? 0);
}

function cliente_logueado(): bool
{
    iniciar_sesion();
    return !empty($_SESSION['cliente_id']);
}

function exigir_cliente(): void
{
    if (!cliente_logueado()) {
        header('Location: ' . base_path() . 'login.php');
        exit;
    }
}

function cliente_id_sesion(): int
{
    return (int) ($_SESSION['cliente_id'] ?? 0);
}

function panel_logueado(): bool
{
    return admin_logueado() || comercio_logueado() || cliente_logueado();
}

/**
 * Limpia sesión de panel (admin, comercio o cliente).
 */
function cerrar_sesion_panel(): void
{
    iniciar_sesion();
    unset(
        $_SESSION['admin_id'],
        $_SESSION['admin_nombre'],
        $_SESSION['comercio_user_id'],
        $_SESSION['comercio_id'],
        $_SESSION['comercio_nombre'],
        $_SESSION['comercio_local'],
        $_SESSION['cliente_id'],
        $_SESSION['cliente_nombre'],
        $_SESSION['cliente_correo'],
        $_SESSION['cliente_telefono'],
        $_SESSION['cliente_direccion']
    );
}

/**
 * Inicia sesión según rol y devuelve la URL de destino del panel.
 *
 * @param array{id_usuario:int|string,nombre:string,rol:string,correo?:string,telefono?:string|null,direccion?:string|null,id_comercio?:int|string|null,local?:string|null} $row
 */
function iniciar_sesion_por_rol(array $row): string
{
    iniciar_sesion();
    cerrar_sesion_panel();

    $rol = (string) ($row['rol'] ?? '');
    if ($rol === 'admin') {
        $_SESSION['admin_id'] = (int) $row['id_usuario'];
        $_SESSION['admin_nombre'] = (string) $row['nombre'];
        return 'admin/index.php';
    }

    if ($rol === 'comercio') {
        $idComercio = (int) ($row['id_comercio'] ?? 0);
        if ($idComercio <= 0) {
            return '';
        }
        $_SESSION['comercio_user_id'] = (int) $row['id_usuario'];
        $_SESSION['comercio_id'] = $idComercio;
        $_SESSION['comercio_nombre'] = (string) $row['nombre'];
        $_SESSION['comercio_local'] = (string) ($row['local'] ?? 'Mi local');
        return 'comercio/index.php';
    }

    if ($rol === 'cliente') {
        $_SESSION['cliente_id'] = (int) $row['id_usuario'];
        $_SESSION['cliente_nombre'] = (string) $row['nombre'];
        $_SESSION['cliente_correo'] = (string) ($row['correo'] ?? '');
        $_SESSION['cliente_telefono'] = (string) ($row['telefono'] ?? '');
        $_SESSION['cliente_direccion'] = (string) ($row['direccion'] ?? '');
        return 'cliente/index.php';
    }

    return '';
}

/**
 * Recarga datos de perfil cliente en sesión.
 */
function refrescar_sesion_cliente(mysqli $mysqli): void
{
    if (!cliente_logueado()) {
        return;
    }
    $id = cliente_id_sesion();
    $stmt = $mysqli->prepare(
        "SELECT id_usuario, nombre, correo, telefono, direccion
         FROM usuario WHERE id_usuario = ? AND rol = 'cliente' LIMIT 1"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row === null) {
        cerrar_sesion_panel();
        return;
    }
    $_SESSION['cliente_id'] = (int) $row['id_usuario'];
    $_SESSION['cliente_nombre'] = (string) $row['nombre'];
    $_SESSION['cliente_correo'] = (string) $row['correo'];
    $_SESSION['cliente_telefono'] = (string) ($row['telefono'] ?? '');
    $_SESSION['cliente_direccion'] = (string) ($row['direccion'] ?? '');
}

function etiqueta_estado(string $estado): string
{
    return match ($estado) {
        'nuevo'       => 'Nuevo',
        'confirmado'  => 'Confirmado',
        'entregado'   => 'Entregado',
        'cancelado'   => 'Cancelado',
        default       => $estado,
    };
}

/**
 * Clase is-active si la URL actual contiene alguno de los fragmentos.
 */
function nav_activo(string ...$fragmentos): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    foreach ($fragmentos as $frag) {
        if ($frag !== '' && str_contains($script, $frag)) {
            return ' is-active';
        }
    }
    return '';
}

/**
 * Contexto de navegación: publico | cliente | comercio | admin.
 */
function nav_contexto(): string
{
    if (comercio_logueado()) {
        return 'comercio';
    }
    if (admin_logueado()) {
        return 'admin';
    }
    if (cliente_logueado()) {
        return 'cliente';
    }
    return 'publico';
}

/**
 * Decodifica ítems del carrito enviados como JSON desde el formulario.
 *
 * @return list<array{id_producto:int,nombre:string,cantidad:int,precio:float}>
 */
function parsear_items_pedido(?string $json): array
{
    if ($json === null || trim($json) === '') {
        return [];
    }
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return [];
    }
    $out = [];
    foreach ($data as $row) {
        if (!is_array($row)) {
            continue;
        }
        $nombre = trim((string) ($row['nombre'] ?? ''));
        $cantidad = (int) ($row['qty'] ?? $row['cantidad'] ?? 0);
        $precio = (float) ($row['precio'] ?? 0);
        $idProducto = (int) ($row['id'] ?? $row['id_producto'] ?? 0);
        if ($nombre === '' || $cantidad < 1) {
            continue;
        }
        $out[] = [
            'id_producto' => $idProducto > 0 ? $idProducto : 0,
            'nombre'      => mb_substr($nombre, 0, 100),
            'cantidad'    => min($cantidad, 99),
            'precio'      => max(0, $precio),
        ];
    }
    return $out;
}

/**
 * Inserta filas en pedido_item (ignora si la tabla aún no existe).
 *
 * @param list<array{id_producto:int,nombre:string,cantidad:int,precio:float}> $items
 */
function guardar_items_pedido(mysqli $mysqli, int $idPedido, array $items): void
{
    if (count($items) === 0) {
        return;
    }
    try {
        $conProd = $mysqli->prepare(
            'INSERT INTO pedido_item (id_pedido, id_producto, nombre, cantidad, precio_unitario)
             VALUES (?, ?, ?, ?, ?)'
        );
        $sinProd = $mysqli->prepare(
            'INSERT INTO pedido_item (id_pedido, id_producto, nombre, cantidad, precio_unitario)
             VALUES (?, NULL, ?, ?, ?)'
        );
        foreach ($items as $it) {
            $nombre = $it['nombre'];
            $cant = $it['cantidad'];
            $precio = $it['precio'];
            if ($it['id_producto'] > 0) {
                $idProd = $it['id_producto'];
                $conProd->bind_param('iisid', $idPedido, $idProd, $nombre, $cant, $precio);
                $conProd->execute();
            } else {
                $sinProd->bind_param('isid', $idPedido, $nombre, $cant, $precio);
                $sinProd->execute();
            }
        }
    } catch (mysqli_sql_exception $e) {
        // Migración no aplicada: el pedido igual queda con detalle TEXT.
    }
}

/* ---------- Carrito (sesión, un comercio a la vez) ---------- */

/**
 * @return array{id_comercio:int, items:array<int,int>}
 */
function carrito_estado(): array
{
    iniciar_sesion();
    $c = $_SESSION['carrito'] ?? null;
    if (!is_array($c) || empty($c['id_comercio']) || empty($c['items']) || !is_array($c['items'])) {
        return ['id_comercio' => 0, 'items' => []];
    }
    $items = [];
    foreach ($c['items'] as $id => $qty) {
        $id = (int) $id;
        $qty = (int) $qty;
        if ($id > 0 && $qty > 0) {
            $items[$id] = min($qty, 99);
        }
    }
    return ['id_comercio' => (int) $c['id_comercio'], 'items' => $items];
}

function carrito_cantidad_total(): int
{
    return array_sum(carrito_estado()['items']);
}

function carrito_vaciar(): void
{
    iniciar_sesion();
    unset($_SESSION['carrito']);
}

function carrito_agregar(int $idComercio, int $idProducto, int $cantidad = 1): void
{
    iniciar_sesion();
    $cantidad = max(1, min($cantidad, 99));
    $estado = carrito_estado();
    if ($estado['id_comercio'] > 0 && $estado['id_comercio'] !== $idComercio) {
        $estado = ['id_comercio' => $idComercio, 'items' => []];
    }
    if ($estado['id_comercio'] === 0) {
        $estado['id_comercio'] = $idComercio;
        $estado['items'] = [];
    }
    $estado['items'][$idProducto] = min(($estado['items'][$idProducto] ?? 0) + $cantidad, 99);
    $_SESSION['carrito'] = $estado;
}

function carrito_fijar(int $idProducto, int $cantidad): void
{
    iniciar_sesion();
    $estado = carrito_estado();
    if ($estado['id_comercio'] <= 0) {
        return;
    }
    if ($cantidad <= 0) {
        unset($estado['items'][$idProducto]);
    } else {
        $estado['items'][$idProducto] = min($cantidad, 99);
    }
    if (count($estado['items']) === 0) {
        unset($_SESSION['carrito']);
        return;
    }
    $_SESSION['carrito'] = $estado;
}

/**
 * @return list<array{id_producto:int,nombre:string,precio:float,cantidad:int,foto_url:?string,subtotal:float}>
 */
function carrito_detalle(mysqli $mysqli): array
{
    $estado = carrito_estado();
    if ($estado['id_comercio'] <= 0 || count($estado['items']) === 0) {
        return [];
    }
    $ids = array_keys($estado['items']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = $mysqli->prepare(
        "SELECT id_producto, nombre, precio, foto_url, disponible, id_comercio
         FROM producto
         WHERE id_producto IN ($placeholders) AND id_comercio = ?"
    );
    $params = [...$ids, $estado['id_comercio']];
    $stmt->bind_param($types . 'i', ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $byId = [];
    foreach ($rows as $r) {
        $byId[(int) $r['id_producto']] = $r;
    }
    $out = [];
    $vigentes = [];
    foreach ($estado['items'] as $id => $qty) {
        if (!isset($byId[$id]) || !(int) $byId[$id]['disponible']) {
            continue;
        }
        $p = $byId[$id];
        $precio = (float) $p['precio'];
        $out[] = [
            'id_producto' => $id,
            'nombre'      => (string) $p['nombre'],
            'precio'      => $precio,
            'cantidad'    => $qty,
            'foto_url'    => $p['foto_url'] ?? null,
            'subtotal'    => $precio * $qty,
        ];
        $vigentes[$id] = $qty;
    }
    if (count($vigentes) !== count($estado['items'])) {
        if (count($vigentes) === 0) {
            carrito_vaciar();
        } else {
            $_SESSION['carrito'] = ['id_comercio' => $estado['id_comercio'], 'items' => $vigentes];
        }
    }
    return $out;
}

function carrito_total(mysqli $mysqli): float
{
    $t = 0.0;
    foreach (carrito_detalle($mysqli) as $it) {
        $t += $it['subtotal'];
    }
    return $t;
}

function carrito_id_comercio(): int
{
    return carrito_estado()['id_comercio'];
}

function cssVersion(): string
{
    $path = __DIR__ . '/estilos.css';
    return is_file($path) ? (string) filemtime($path) : '1';
}

function enviarHeadersCache(string $modo = 'privado'): void
{
    if (headers_sent()) {
        return;
    }
    if ($modo === 'publico') {
        // HTML breve; la fuente de verdad es el caché de archivos (TTL 4 h + parche al editar).
        header('Cache-Control: public, max-age=120, stale-while-revalidate=60');
        header('Vary: Cookie');
    } else {
        header('Cache-Control: no-store, private');
    }
}

/* ——— Lecturas cacheadas (hosting compartido) ——— */

/** Categorías con conteo de comercios activos (home). */
function obtenerCategoriasHome(mysqli $db): array
{
    $cached = cacheGet('categorias_home');
    if (is_array($cached)) {
        return $cached;
    }
    $rows = $db->query(
        'SELECT cat.id_categoria_comercio, cat.nombre, cat.slug, cat.foto_url, cat.color,
                COUNT(com.id_comercio) AS total
         FROM categoria_comercio cat
         LEFT JOIN comercio com ON com.id_categoria_comercio = cat.id_categoria_comercio AND com.activo = 1
         GROUP BY cat.id_categoria_comercio, cat.nombre, cat.slug, cat.foto_url, cat.color
         ORDER BY cat.nombre'
    )->fetch_all(MYSQLI_ASSOC);
    cacheSet('categorias_home', $rows, CACHE_TTL_HOME);
    return $rows;
}

/** Listado simple de categorías (filtros). */
function obtenerCategorias(mysqli $db): array
{
    $cached = cacheGet('categorias');
    if (is_array($cached)) {
        return $cached;
    }
    $rows = $db->query(
        'SELECT id_categoria_comercio, nombre FROM categoria_comercio ORDER BY nombre'
    )->fetch_all(MYSQLI_ASSOC);
    cacheSet('categorias', $rows, CACHE_TTL_DEFAULT);
    return $rows;
}

function obtenerCategoria(mysqli $db, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $key = 'categoria_' . $id;
    $cached = cacheGet($key);
    if (is_array($cached)) {
        return $cached;
    }
    if ($cached === false) {
        return null;
    }
    $stmt = $db->prepare(
        'SELECT id_categoria_comercio, nombre, foto_url FROM categoria_comercio WHERE id_categoria_comercio = ?'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        cacheSet($key, $row, CACHE_TTL_DEFAULT);
        return $row;
    }
    cacheSet($key, false, CACHE_TTL_NEGATIVO);
    return null;
}

/**
 * Totales del home.
 * @return array{comercios:int,productos:int,pedidos:int}
 */
function obtenerTotalesHome(mysqli $db): array
{
    $cached = cacheGet('totales');
    if (is_array($cached) && isset($cached['comercios'], $cached['productos'], $cached['pedidos'])) {
        return $cached;
    }
    $totales = [
        'comercios' => (int) $db->query('SELECT COUNT(*) AS n FROM comercio WHERE activo = 1')->fetch_assoc()['n'],
        'productos' => (int) $db->query('SELECT COUNT(*) AS n FROM producto WHERE disponible = 1')->fetch_assoc()['n'],
        'pedidos'   => (int) $db->query('SELECT COUNT(*) AS n FROM pedido')->fetch_assoc()['n'],
    ];
    cacheSet('totales', $totales, CACHE_TTL_HOME);
    return $totales;
}

/** Top 3 locales más pedidos. */
function obtenerDestacados(mysqli $db): array
{
    $cached = cacheGet('destacados');
    if (is_array($cached)) {
        return $cached;
    }
    $rows = $db->query(
        'SELECT c.id_comercio, c.nombre, c.direccion, c.foto_url, c.descripcion, cat.nombre AS categoria,
                COUNT(p.id_pedido) AS n_pedidos
         FROM comercio c
         JOIN categoria_comercio cat ON cat.id_categoria_comercio = c.id_categoria_comercio
         LEFT JOIN pedido p ON p.id_comercio = c.id_comercio
         WHERE c.activo = 1
         GROUP BY c.id_comercio, c.nombre, c.direccion, c.foto_url, c.descripcion, cat.nombre
         ORDER BY n_pedidos DESC, c.nombre
         LIMIT 3'
    )->fetch_all(MYSQLI_ASSOC);
    cacheSet('destacados', $rows, CACHE_TTL_HOME);
    return $rows;
}

/**
 * Locales activos. Cachea sin búsqueda de texto.
 * @return list<array<string,mixed>>
 */
function obtenerLocales(mysqli $db, int $idCat = 0, string $q = ''): array
{
    $q = trim($q);
    $usarCache = ($q === '');
    $cacheKey = 'locales_' . $idCat;
    if ($usarCache) {
        $cached = cacheGet($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $sql = 'SELECT c.id_comercio, c.nombre, c.direccion, c.telefono, c.descripcion, c.horario,
                   c.foto_url, c.latitud, c.longitud, c.id_categoria_comercio, cat.nombre AS categoria
            FROM comercio c
            JOIN categoria_comercio cat ON cat.id_categoria_comercio = c.id_categoria_comercio
            WHERE c.activo = 1';
    $types = '';
    $params = [];
    if ($idCat > 0) {
        $sql .= ' AND c.id_categoria_comercio = ?';
        $types .= 'i';
        $params[] = $idCat;
    }
    if ($q !== '') {
        $sql .= ' AND (c.nombre LIKE ? OR c.direccion LIKE ? OR c.descripcion LIKE ? OR cat.nombre LIKE ?)';
        $like = '%' . $q . '%';
        $types .= 'ssss';
        array_push($params, $like, $like, $like, $like);
    }
    $sql .= ' ORDER BY c.nombre';
    $stmt = $db->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if ($usarCache) {
        cacheSet($cacheKey, $rows, CACHE_TTL_DEFAULT);
    }
    return $rows;
}

/**
 * Locales con coordenadas (mapa). Cachea sin búsqueda.
 * @return list<array<string,mixed>>
 */
function obtenerLocalesMapa(mysqli $db, int $idCat = 0, string $q = ''): array
{
    $q = trim($q);
    $usarCache = ($q === '');
    $cacheKey = 'mapa_' . $idCat;
    if ($usarCache) {
        $cached = cacheGet($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $sql = 'SELECT c.id_comercio, c.nombre, c.direccion, c.telefono, c.descripcion, c.foto_url,
                   c.latitud, c.longitud, c.id_categoria_comercio, cat.nombre AS categoria
            FROM comercio c
            JOIN categoria_comercio cat ON cat.id_categoria_comercio = c.id_categoria_comercio
            WHERE c.activo = 1
              AND c.latitud IS NOT NULL AND c.longitud IS NOT NULL
              AND c.latitud <> 0 AND c.longitud <> 0';
    $types = '';
    $params = [];
    if ($idCat > 0) {
        $sql .= ' AND c.id_categoria_comercio = ?';
        $types .= 'i';
        $params[] = $idCat;
    }
    if ($q !== '') {
        $sql .= ' AND (c.nombre LIKE ? OR c.direccion LIKE ? OR cat.nombre LIKE ?)';
        $like = '%' . $q . '%';
        $types .= 'sss';
        array_push($params, $like, $like, $like);
    }
    $sql .= ' ORDER BY c.nombre';
    $stmt = $db->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if ($usarCache) {
        cacheSet($cacheKey, $rows, CACHE_TTL_DEFAULT);
    }
    return $rows;
}

function obtenerComercioActivo(mysqli $db, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $key = 'comercio_' . $id;
    $cached = cacheGet($key);
    if (is_array($cached)) {
        return $cached;
    }
    if ($cached === false) {
        return null;
    }
    $stmt = $db->prepare(
        'SELECT c.id_comercio, c.nombre, c.direccion, c.latitud, c.longitud, c.telefono, c.descripcion, c.horario,
                c.foto_url, c.id_categoria_comercio, c.activo, cat.nombre AS categoria
         FROM comercio c
         JOIN categoria_comercio cat ON cat.id_categoria_comercio = c.id_categoria_comercio
         WHERE c.id_comercio = ? AND c.activo = 1'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        cacheSet($key, $row, CACHE_TTL_DEFAULT);
        return $row;
    }
    cacheSet($key, false, CACHE_TTL_NEGATIVO);
    return null;
}

/** Carga comercio + categoría (activo o no). */
function cargarComercioConCategoria(mysqli $db, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $stmt = $db->prepare(
        'SELECT c.*, cat.nombre AS categoria
         FROM comercio c
         JOIN categoria_comercio cat ON cat.id_categoria_comercio = c.id_categoria_comercio
         WHERE c.id_comercio = ?'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

/**
 * Catálogo de productos de un local (todos; la vista marca agotados).
 * @return list<array<string,mixed>>
 */
function obtenerProductosComercio(mysqli $db, int $idComercio): array
{
    if ($idComercio <= 0) {
        return [];
    }
    $key = 'productos_' . $idComercio;
    $cached = cacheGet($key);
    if (is_array($cached)) {
        return $cached;
    }
    $stmt = $db->prepare(
        'SELECT id_producto, nombre, precio, descripcion, disponible, foto_url, id_comercio
         FROM producto
         WHERE id_comercio = ?
         ORDER BY disponible DESC, nombre'
    );
    $stmt->bind_param('i', $idComercio);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    cacheSet($key, $rows, CACHE_TTL_DEFAULT);
    return $rows;
}

/* ——— Parche de caché (write-through): sin wipe total ——— */

/** Quita id_comercio de una lista cacheada. */
function cacheListaQuitarComercio(array $lista, int $idComercio): array
{
    $out = [];
    foreach ($lista as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ((int) ($row['id_comercio'] ?? 0) === $idComercio) {
            continue;
        }
        $out[] = $row;
    }
    return $out;
}

/** Inserta/reemplaza comercio en lista ordenada por nombre. */
function cacheListaUpsertComercio(array $lista, array $comercio): array
{
    $id = (int) ($comercio['id_comercio'] ?? 0);
    $lista = cacheListaQuitarComercio($lista, $id);
    $lista[] = $comercio;
    usort($lista, static function (array $a, array $b): int {
        return strcasecmp((string) ($a['nombre'] ?? ''), (string) ($b['nombre'] ?? ''));
    });
    return $lista;
}

function comercioEntraEnLocales(array $com, int $idCat): bool
{
    if ((int) ($com['activo'] ?? 0) !== 1) {
        return false;
    }
    if ($idCat > 0 && (int) ($com['id_categoria_comercio'] ?? 0) !== $idCat) {
        return false;
    }
    return true;
}

function comercioEntraEnMapa(array $com, int $idCat): bool
{
    if (!comercioEntraEnLocales($com, $idCat)) {
        return false;
    }
    $lat = $com['latitud'] ?? null;
    $lng = $com['longitud'] ?? null;
    if ($lat === null || $lng === null || $lat === '' || $lng === '') {
        return false;
    }
    return (float) $lat !== 0.0 && (float) $lng !== 0.0;
}

/** Fila ligera para listados públicos. */
function comercioFilaListado(array $com): array
{
    return [
        'id_comercio' => (int) ($com['id_comercio'] ?? 0),
        'nombre' => (string) ($com['nombre'] ?? ''),
        'direccion' => (string) ($com['direccion'] ?? ''),
        'telefono' => (string) ($com['telefono'] ?? ''),
        'descripcion' => $com['descripcion'] ?? null,
        'horario' => $com['horario'] ?? null,
        'foto_url' => $com['foto_url'] ?? null,
        'latitud' => $com['latitud'] ?? null,
        'longitud' => $com['longitud'] ?? null,
        'id_categoria_comercio' => (int) ($com['id_categoria_comercio'] ?? 0),
        'categoria' => (string) ($com['categoria'] ?? ''),
    ];
}

/**
 * Parchea ficha + listados locales/mapa ya materializados tras create/update.
 * $antes = fila previa (id_categoria_comercio) si cambió de categoría.
 */
function cacheParcheComercio(mysqli $db, int $idComercio, ?array $antes = null): void
{
    $idComercio = (int) $idComercio;
    if ($idComercio <= 0) {
        return;
    }
    $ahora = cargarComercioConCategoria($db, $idComercio);
    // Listados ya materializados: se quita/reinserta por id (cubre cambio de categoría).
    // $antes queda disponible si en el futuro hace falta un parche más fino.

    if ($ahora && (int) ($ahora['activo'] ?? 0) === 1) {
        cachePatch('comercio_' . $idComercio, $ahora, CACHE_TTL_DEFAULT);
    } else {
        cachePatch('comercio_' . $idComercio, false, CACHE_TTL_NEGATIVO);
    }

    $fila = $ahora ? comercioFilaListado($ahora) : null;

    foreach (cacheKeysByPrefix('locales_') as $key) {
        if (!preg_match('/^locales_(\d+)$/', $key, $m)) {
            continue;
        }
        $idCat = (int) $m[1];
        cachePatchIfPresent($key, static function ($data) use ($fila, $idComercio, $idCat, $ahora) {
            if (!is_array($data)) {
                return $data;
            }
            $data = cacheListaQuitarComercio($data, $idComercio);
            if ($ahora && $fila && comercioEntraEnLocales($ahora, $idCat)) {
                $data = cacheListaUpsertComercio($data, $fila);
            }
            return $data;
        });
    }

    foreach (cacheKeysByPrefix('mapa_') as $key) {
        if (!preg_match('/^mapa_(\d+)$/', $key, $m)) {
            continue;
        }
        $idCat = (int) $m[1];
        cachePatchIfPresent($key, static function ($data) use ($fila, $idComercio, $idCat, $ahora) {
            if (!is_array($data)) {
                return $data;
            }
            $data = cacheListaQuitarComercio($data, $idComercio);
            if ($ahora && $fila && comercioEntraEnMapa($ahora, $idCat)) {
                $data = cacheListaUpsertComercio($data, $fila);
            }
            return $data;
        });
    }

    // Conteos por categoría + totales + destacados (pocas filas; 2–3 queries)
    cacheParcheCategoriasHome($db);
    cacheParcheTotales($db);
    cacheParcheDestacados($db);
}

/** Tras alta de comercio nuevo. */
function cacheParcheComercioNuevo(mysqli $db, int $idComercio): void
{
    cacheParcheComercio($db, $idComercio);
}

/**
 * Parchea catálogo de productos del local + totales de productos.
 */
function cacheParcheProducto(mysqli $db, int $idComercio, ?int $idProducto = null): void
{
    $idComercio = (int) $idComercio;
    if ($idComercio <= 0) {
        return;
    }
    // Regenera solo la clave del menú (1 query scoped) — evita wipe global
    cacheForget('productos_' . $idComercio);
    obtenerProductosComercio($db, $idComercio);
    cacheParcheTotales($db);
}

function cacheParcheTotales(mysqli $db): void
{
    $peek = cachePeek('totales');
    if ($peek === null) {
        return;
    }
    $totales = [
        'comercios' => (int) $db->query('SELECT COUNT(*) AS n FROM comercio WHERE activo = 1')->fetch_assoc()['n'],
        'productos' => (int) $db->query('SELECT COUNT(*) AS n FROM producto WHERE disponible = 1')->fetch_assoc()['n'],
        'pedidos'   => (int) $db->query('SELECT COUNT(*) AS n FROM pedido')->fetch_assoc()['n'],
    ];
    cachePatch('totales', $totales, CACHE_TTL_HOME);
}

function cacheParcheDestacados(mysqli $db): void
{
    $peek = cachePeek('destacados');
    if ($peek === null) {
        return;
    }
    cacheForget('destacados');
    obtenerDestacados($db);
}

function cacheParcheCategoriasHome(mysqli $db): void
{
    $peek = cachePeek('categorias_home');
    if ($peek === null) {
        return;
    }
    cacheForget('categorias_home');
    obtenerCategoriasHome($db);
}

/** Tras crear un pedido: totales + ranking del home. */
function cacheParcheTrasPedido(mysqli $db): void
{
    cacheParcheTotales($db);
    cacheParcheDestacados($db);
}
