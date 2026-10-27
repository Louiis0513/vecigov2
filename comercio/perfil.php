<?php
require_once __DIR__ . '/../funciones.php';
require_once __DIR__ . '/../conexion.php';
exigir_comercio();

$idComercio = comercio_id_sesion();
$errores = [];

$stmt = $mysqli->prepare(
    'SELECT nombre, direccion, latitud, longitud, telefono, descripcion, horario
     FROM comercio WHERE id_comercio = ? LIMIT 1'
);
$stmt->bind_param('i', $idComercio);
$stmt->execute();
$com = $stmt->get_result()->fetch_assoc();
if ($com === null) {
    http_response_code(404);
    exit('Comercio no encontrado');
}

$nombre = $com['nombre'];
$direccion = $com['direccion'];
$latitud = $com['latitud'] !== null ? (string) $com['latitud'] : '';
$longitud = $com['longitud'] !== null ? (string) $com['longitud'] : '';
$telefono = $com['telefono'];
$descripcion = (string) ($com['descripcion'] ?? '');
$horario = (string) ($com['horario'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $latitud = trim($_POST['latitud'] ?? '');
    $longitud = trim($_POST['longitud'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $horario = trim($_POST['horario'] ?? '');

    if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100) {
        $errores['nombre'] = 'Nombre inválido.';
    }
    if (mb_strlen($direccion) < 5 || mb_strlen($direccion) > 150) {
        $errores['direccion'] = 'La dirección exacta es obligatoria (5–150 caracteres).';
    }
    if (!preg_match('/^3\d{9}$/', $telefono)) {
        $errores['telefono'] = 'Celular de 10 dígitos que empiece por 3.';
    }

    if (!is_numeric($latitud) || !is_numeric($longitud)) {
        $errores['coords'] = 'Ubica el local en el mapa (clic o arrastra el pin).';
    } else {
        $latVal = (float) $latitud;
        $lngVal = (float) $longitud;
        if ($latVal < -90 || $latVal > 90 || $lngVal < -180 || $lngVal > 180) {
            $errores['coords'] = 'Coordenadas fuera de rango.';
        }
    }

    if (count($errores) === 0) {
        $antes = cargarComercioConCategoria($mysqli, $idComercio);
        $latVal = (float) $latitud;
        $lngVal = (float) $longitud;
        $stmt = $mysqli->prepare(
            'UPDATE comercio
             SET nombre = ?, direccion = ?, latitud = ?, longitud = ?, telefono = ?,
                 descripcion = ?, horario = ?
             WHERE id_comercio = ?'
        );
        $stmt->bind_param(
            'ssddsssi',
            $nombre,
            $direccion,
            $latVal,
            $lngVal,
            $telefono,
            $descripcion,
            $horario,
            $idComercio
        );
        $stmt->execute();
        cacheParcheComercio($mysqli, $idComercio, $antes);
        $_SESSION['comercio_local'] = $nombre;
        $_SESSION['mensaje'] = 'Datos del local actualizados.';
        header('Location: perfil.php');
        exit;
    }
}

$mensaje = $_SESSION['mensaje'] ?? null;
unset($_SESSION['mensaje']);

$tituloPagina = 'Mi local · VeciGo';
require __DIR__ . '/../includes/header.php';
?>

<div class="contenedor">
    <div class="page-head">
        <div>
            <h1>Datos del local</h1>
            <p>Dirección exacta y pin en el mapa para aparecer en «Mapa del barrio».</p>
        </div>
        <a class="btn btn-line" href="index.php">← Panel</a>
    </div>

    <?php if ($mensaje): ?>
        <div class="alerta alerta-exito"><?= e($mensaje) ?></div>
    <?php endif; ?>

    <div class="card" style="max-width:720px;">
        <form method="post" action="perfil.php">
            <div class="campo">
                <label for="nombre">Nombre del comercio</label>
                <input type="text" id="nombre" name="nombre" value="<?= e($nombre) ?>" required
                       class="<?= isset($errores['nombre']) ? 'invalido' : '' ?>">
                <?php if (isset($errores['nombre'])): ?><span class="error"><?= e($errores['nombre']) ?></span><?php endif; ?>
            </div>
            <div class="campo">
                <label for="direccion">Dirección exacta *</label>
                <input type="text" id="direccion" name="direccion" value="<?= e($direccion) ?>" required
                       placeholder="Calle, número, barrio, ciudad"
                       class="<?= isset($errores['direccion']) ? 'invalido' : '' ?>">
                <?php if (isset($errores['direccion'])): ?><span class="error"><?= e($errores['direccion']) ?></span><?php endif; ?>
            </div>

            <div class="campo">
                <label>Ubicación en el mapa *</label>
                <p class="hint" style="margin-bottom:0.6rem;">
                    Haz clic en el mapa o arrastra el pin. Si dejas la dirección vacía, se sugiere al marcar el punto.
                </p>
                <div id="mapaPicker" class="mapa-picker" style="width:100%;height:320px;min-height:320px"
                     role="region" aria-label="Selector de ubicación"></div>
                <p class="mapa-error" data-map-error hidden></p>
                <?php if (isset($errores['coords'])): ?>
                    <span class="error"><?= e($errores['coords']) ?></span>
                <?php endif; ?>
            </div>
            <div class="campo-grid-2">
                <div class="campo">
                    <label for="latitud">Latitud</label>
                    <input type="text" id="latitud" name="latitud" value="<?= e($latitud) ?>" readonly required>
                </div>
                <div class="campo">
                    <label for="longitud">Longitud</label>
                    <input type="text" id="longitud" name="longitud" value="<?= e($longitud) ?>" readonly required>
                </div>
            </div>

            <div class="campo">
                <label for="telefono">Celular WhatsApp</label>
                <input type="tel" id="telefono" name="telefono" value="<?= e($telefono) ?>" required
                       class="<?= isset($errores['telefono']) ? 'invalido' : '' ?>">
                <?php if (isset($errores['telefono'])): ?><span class="error"><?= e($errores['telefono']) ?></span><?php endif; ?>
            </div>
            <div class="campo">
                <label for="horario">Horario</label>
                <input type="text" id="horario" name="horario" value="<?= e($horario) ?>">
            </div>
            <div class="campo">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="3"><?= e($descripcion) ?></textarea>
            </div>
            <button class="btn btn-coral" type="submit">Guardar</button>
            <a class="btn btn-line" href="<?= e(base_path()) ?>locales_mapa.php" style="margin-left:0.5rem;">Ver mapa</a>
        </form>
    </div>
</div>

<script src="<?= e(asset('js/mapa.js')) ?>"></script>
<script>
VecigoMap.picker('mapaPicker', {
  latInput: 'latitud',
  lngInput: 'longitud',
  addressInput: 'direccion',
  lat: <?= json_encode($latitud !== '' ? (float) $latitud : null) ?>,
  lng: <?= json_encode($longitud !== '' ? (float) $longitud : null) ?>,
  zoom: 16
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
