<?php
require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/conexion.php';
iniciar_sesion();

if (panel_logueado()) {
    $ctx = nav_contexto();
    $map = ['admin' => 'admin/index.php', 'comercio' => 'comercio/index.php', 'cliente' => 'cliente/index.php'];
    header('Location: ' . ($map[$ctx] ?? 'index.php'));
    exit;
}

$categorias = obtenerCategorias($mysqli);

$nombreDueno = $correo = $telefono = '';
$nombreLocal = $direccion = $horario = $descripcion = '';
$idCategoria = 0;
$latitud = $longitud = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreDueno = trim($_POST['nombre_dueno'] ?? '');
    $correo = mb_strtolower(trim($_POST['correo'] ?? ''));
    $telefono = trim($_POST['telefono'] ?? '');
    $clave = $_POST['clave'] ?? '';
    $clave2 = $_POST['clave2'] ?? '';

    $nombreLocal = trim($_POST['nombre_local'] ?? '');
    $idCategoria = (int) ($_POST['id_categoria'] ?? 0);
    $direccion = trim($_POST['direccion'] ?? '');
    $latitud = trim($_POST['latitud'] ?? '');
    $longitud = trim($_POST['longitud'] ?? '');
    $horario = trim($_POST['horario'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if (mb_strlen($nombreDueno) < 3 || mb_strlen($nombreDueno) > 100) {
        $errores['nombre_dueno'] = 'Nombre entre 3 y 100 caracteres.';
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 120) {
        $errores['correo'] = 'Correo inválido.';
    }
    if (!preg_match('/^3\d{9}$/', $telefono)) {
        $errores['telefono'] = 'Celular de 10 dígitos que empiece por 3.';
    }
    if (mb_strlen($clave) < 6) {
        $errores['clave'] = 'Mínimo 6 caracteres.';
    } elseif ($clave !== $clave2) {
        $errores['clave2'] = 'Las contraseñas no coinciden.';
    }
    if (mb_strlen($nombreLocal) < 2 || mb_strlen($nombreLocal) > 100) {
        $errores['nombre_local'] = 'Nombre del local inválido.';
    }
    $catOk = false;
    foreach ($categorias as $cat) {
        if ((int) $cat['id_categoria_comercio'] === $idCategoria) {
            $catOk = true;
            break;
        }
    }
    if (!$catOk) {
        $errores['id_categoria'] = 'Elige una categoría.';
    }
    if (mb_strlen($direccion) < 5 || mb_strlen($direccion) > 150) {
        $errores['direccion'] = 'Escribe la dirección exacta del local (5–150 caracteres).';
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
    if ($horario !== '' && mb_strlen($horario) > 80) {
        $errores['horario'] = 'Horario demasiado largo.';
    }
    if ($descripcion !== '' && mb_strlen($descripcion) > 255) {
        $errores['descripcion'] = 'Descripción máximo 255 caracteres.';
    }

    if (count($errores) === 0) {
        $stmt = $mysqli->prepare('SELECT id_usuario FROM usuario WHERE correo = ? LIMIT 1');
        $stmt->bind_param('s', $correo);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $errores['correo'] = 'Ya existe una cuenta con este correo.';
        }
    }

    if (count($errores) === 0) {
        $baseUser = preg_replace('/[^a-z0-9]/', '', strstr($correo, '@', true) ?: 'local') ?: 'local';
        $baseUser = mb_substr($baseUser, 0, 40);
        $usuario = $baseUser;
        $n = 1;
        while (true) {
            $chk = $mysqli->prepare('SELECT id_usuario FROM usuario WHERE usuario = ? LIMIT 1');
            $chk->bind_param('s', $usuario);
            $chk->execute();
            if (!$chk->get_result()->fetch_assoc()) {
                break;
            }
            $usuario = $baseUser . $n;
            $n++;
            if ($n > 99) {
                $usuario = $baseUser . bin2hex(random_bytes(2));
                break;
            }
        }

        $hash = password_hash($clave, PASSWORD_DEFAULT);
        $rol = 'comercio';
        $horarioDb = $horario !== '' ? $horario : 'Lun–Sáb 8:00–20:00';
        $descDb = $descripcion;
        $latVal = (float) $latitud;
        $lngVal = (float) $longitud;

        try {
            $mysqli->begin_transaction();

            $insC = $mysqli->prepare(
                'INSERT INTO comercio
                    (nombre, id_categoria_comercio, direccion, latitud, longitud, telefono, descripcion, horario, activo)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)'
            );
            $insC->bind_param(
                'sisddsss',
                $nombreLocal,
                $idCategoria,
                $direccion,
                $latVal,
                $lngVal,
                $telefono,
                $descDb,
                $horarioDb
            );
            $insC->execute();
            $idComercio = (int) $insC->insert_id;

            $insU = $mysqli->prepare(
                'INSERT INTO usuario (nombre, usuario, correo, telefono, direccion, clave, rol, id_comercio)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insU->bind_param(
                'sssssssi',
                $nombreDueno,
                $usuario,
                $correo,
                $telefono,
                $direccion,
                $hash,
                $rol,
                $idComercio
            );
            $insU->execute();
            $idUser = (int) $insU->insert_id;

            $mysqli->commit();
            cacheParcheComercioNuevo($mysqli, $idComercio);

            $destino = iniciar_sesion_por_rol([
                'id_usuario'  => $idUser,
                'nombre'      => $nombreDueno,
                'correo'      => $correo,
                'telefono'    => $telefono,
                'direccion'   => $direccion,
                'rol'         => 'comercio',
                'id_comercio' => $idComercio,
                'local'       => $nombreLocal,
            ]);
            $_SESSION['mensaje'] = 'Local creado. Ya puedes cargar tu menú y pedidos.';
            header('Location: ' . ($destino !== '' ? $destino : 'comercio/index.php'));
            exit;
        } catch (Throwable $e) {
            $mysqli->rollback();
            $errores['general'] = 'No se pudo crear el local. Intenta de nuevo.';
        }
    }
}

$tituloPagina = 'Registrar mi local · VeciGo';
require __DIR__ . '/includes/header.php';
?>

<div class="contenedor" style="max-width:720px;">
    <div class="page-head">
        <div>
            <h1>Registrar mi local</h1>
            <p>Crea la cuenta del comercio, la dirección exacta y ubícalo en el mapa del barrio.</p>
        </div>
    </div>

    <div class="card">
        <?php if (isset($errores['general'])): ?>
            <div class="alerta alerta-error"><?= e($errores['general']) ?></div>
        <?php endif; ?>

        <form method="post" action="registro_comercio.php" autocomplete="on">
            <h2 class="form-section-title">Tu cuenta</h2>
            <div class="campo">
                <label for="nombre_dueno">Tu nombre</label>
                <input type="text" id="nombre_dueno" name="nombre_dueno" value="<?= e($nombreDueno) ?>" required
                       class="<?= isset($errores['nombre_dueno']) ? 'invalido' : '' ?>">
                <?php if (isset($errores['nombre_dueno'])): ?><span class="error"><?= e($errores['nombre_dueno']) ?></span><?php endif; ?>
            </div>
            <div class="campo">
                <label for="correo">Correo (para entrar)</label>
                <input type="email" id="correo" name="correo" value="<?= e($correo) ?>" required
                       class="<?= isset($errores['correo']) ? 'invalido' : '' ?>">
                <?php if (isset($errores['correo'])): ?><span class="error"><?= e($errores['correo']) ?></span><?php endif; ?>
            </div>
            <div class="campo">
                <label for="telefono">Celular WhatsApp del local</label>
                <input type="tel" id="telefono" name="telefono" value="<?= e($telefono) ?>"
                       placeholder="3001234567" required
                       class="<?= isset($errores['telefono']) ? 'invalido' : '' ?>">
                <?php if (isset($errores['telefono'])): ?><span class="error"><?= e($errores['telefono']) ?></span><?php endif; ?>
            </div>
            <div class="campo-grid-2">
                <div class="campo">
                    <label for="clave">Contraseña</label>
                    <input type="password" id="clave" name="clave" required minlength="6"
                           class="<?= isset($errores['clave']) ? 'invalido' : '' ?>">
                    <?php if (isset($errores['clave'])): ?><span class="error"><?= e($errores['clave']) ?></span><?php endif; ?>
                </div>
                <div class="campo">
                    <label for="clave2">Repetir contraseña</label>
                    <input type="password" id="clave2" name="clave2" required minlength="6"
                           class="<?= isset($errores['clave2']) ? 'invalido' : '' ?>">
                    <?php if (isset($errores['clave2'])): ?><span class="error"><?= e($errores['clave2']) ?></span><?php endif; ?>
                </div>
            </div>

            <h2 class="form-section-title">El local</h2>
            <div class="campo">
                <label for="nombre_local">Nombre del comercio</label>
                <input type="text" id="nombre_local" name="nombre_local" value="<?= e($nombreLocal) ?>" required
                       class="<?= isset($errores['nombre_local']) ? 'invalido' : '' ?>">
                <?php if (isset($errores['nombre_local'])): ?><span class="error"><?= e($errores['nombre_local']) ?></span><?php endif; ?>
            </div>
            <div class="campo">
                <label for="id_categoria">Categoría</label>
                <select id="id_categoria" name="id_categoria" required
                        class="<?= isset($errores['id_categoria']) ? 'invalido' : '' ?>">
                    <option value="0">Elige…</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= (int) $cat['id_categoria_comercio'] ?>"
                            <?= $idCategoria === (int) $cat['id_categoria_comercio'] ? 'selected' : '' ?>>
                            <?= e($cat['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errores['id_categoria'])): ?><span class="error"><?= e($errores['id_categoria']) ?></span><?php endif; ?>
            </div>
            <div class="campo">
                <label for="direccion">Dirección exacta *</label>
                <input type="text" id="direccion" name="direccion" value="<?= e($direccion) ?>" required
                       placeholder="Ej. Calle 12 # 8-30, La Soledad, Bogotá"
                       class="<?= isset($errores['direccion']) ? 'invalido' : '' ?>">
                <?php if (isset($errores['direccion'])): ?><span class="error"><?= e($errores['direccion']) ?></span><?php endif; ?>
            </div>

            <div class="campo">
                <label>Ubicación en el mapa *</label>
                <p class="hint" style="margin-bottom:0.6rem;">
                    Haz clic en el mapa o arrastra el pin para marcar dónde está el local.
                    Si la dirección está vacía, se sugiere al tocar el mapa.
                </p>
                <div id="mapaPicker" class="mapa-picker" style="width:100%;height:320px;min-height:320px"
                     role="region" aria-label="Selector de ubicación del local"></div>
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
                <label for="horario">Horario</label>
                <input type="text" id="horario" name="horario" value="<?= e($horario) ?>"
                       placeholder="Lun–Sáb 8:00–20:00"
                       class="<?= isset($errores['horario']) ? 'invalido' : '' ?>">
                <?php if (isset($errores['horario'])): ?><span class="error"><?= e($errores['horario']) ?></span><?php endif; ?>
            </div>
            <div class="campo">
                <label for="descripcion">Descripción corta</label>
                <textarea id="descripcion" name="descripcion" rows="3"
                          class="<?= isset($errores['descripcion']) ? 'invalido' : '' ?>"><?= e($descripcion) ?></textarea>
                <?php if (isset($errores['descripcion'])): ?><span class="error"><?= e($errores['descripcion']) ?></span><?php endif; ?>
            </div>

            <button class="btn btn-coral btn-block" type="submit">Crear local y entrar</button>
        </form>

        <p class="hint" style="margin-top:1.2rem;margin-bottom:0;text-align:center;">
            ¿Ya tienes cuenta?
            <a href="login.php" style="color:var(--coral);font-weight:700;">Entrar</a>
            · ¿Eres cliente?
            <a href="registro.php" style="color:var(--coral);font-weight:700;">Crear cuenta cliente</a>
        </p>
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
  zoom: 14
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
