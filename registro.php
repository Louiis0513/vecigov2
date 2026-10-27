<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/conexion.php';
iniciar_sesion();

if (panel_logueado()) {
    $ctx = nav_contexto();
    $map = ['admin' => 'admin/index.php', 'comercio' => 'comercio/index.php', 'cliente' => 'cliente/index.php'];
    header('Location: ' . ($map[$ctx] ?? 'index.php'));
    exit;
}

$nombre = $correo = $telefono = $direccion = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = mb_strtolower(trim($_POST['correo'] ?? ''));
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $clave = $_POST['clave'] ?? '';
    $clave2 = $_POST['clave2'] ?? '';

    if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 100) {
        $errores['nombre'] = 'Nombre entre 3 y 100 caracteres.';
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 120) {
        $errores['correo'] = 'Correo inválido.';
    }
    if (!preg_match('/^3\d{9}$/', $telefono)) {
        $errores['telefono'] = 'Celular de 10 dígitos que empiece por 3.';
    }
    if (mb_strlen($direccion) < 5 || mb_strlen($direccion) > 150) {
        $errores['direccion'] = 'Escribe tu dirección de entrega.';
    }
    if (mb_strlen($clave) < 6) {
        $errores['clave'] = 'Mínimo 6 caracteres.';
    } elseif ($clave !== $clave2) {
        $errores['clave2'] = 'Las contraseñas no coinciden.';
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
        $baseUser = preg_replace('/[^a-z0-9]/', '', strstr($correo, '@', true) ?: 'cliente') ?: 'cliente';
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
        $rol = 'cliente';
        try {
            $stmt = $mysqli->prepare(
                'INSERT INTO usuario (nombre, usuario, correo, telefono, direccion, clave, rol)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('sssssss', $nombre, $usuario, $correo, $telefono, $direccion, $hash, $rol);
            $stmt->execute();
            $id = (int) $stmt->insert_id;

            // Vincular pedidos previos del mismo celular
            $link = $mysqli->prepare(
                'UPDATE pedido SET id_usuario = ? WHERE telefono_cliente = ? AND id_usuario IS NULL'
            );
            $link->bind_param('is', $id, $telefono);
            $link->execute();

            $destino = iniciar_sesion_por_rol([
                'id_usuario' => $id,
                'nombre'     => $nombre,
                'correo'     => $correo,
                'telefono'   => $telefono,
                'direccion'  => $direccion,
                'rol'        => 'cliente',
            ]);
            header('Location: ' . ($destino !== '' ? $destino : 'cliente/index.php'));
            exit;
        } catch (mysqli_sql_exception $e) {
            $errores['general'] = 'No se pudo crear la cuenta. Intenta de nuevo.';
        }
    }
}

$tituloPagina = 'Crear cuenta · VeciGo';
require __DIR__ . '/includes/header.php';
?>

<div class="login-wrap" style="max-width:480px;">
    <div class="login-card">
        <h1>Crear cuenta</h1>
        <p class="hint">Guarda tu info y sigue tus pedidos del barrio.</p>

        <?php if (isset($errores['general'])): ?>
            <div class="alerta alerta-error"><?= e($errores['general']) ?></div>
        <?php endif; ?>

        <form method="post" action="registro.php" autocomplete="on">
            <div class="campo">
                <label for="nombre">Tu nombre</label>
                <input type="text" id="nombre" name="nombre" value="<?= e($nombre) ?>" required
                       class="<?= isset($errores['nombre']) ? 'invalido' : '' ?>">
                <?php if (isset($errores['nombre'])): ?><span class="error"><?= e($errores['nombre']) ?></span><?php endif; ?>
            </div>
            <div class="campo">
                <label for="correo">Correo</label>
                <input type="email" id="correo" name="correo" value="<?= e($correo) ?>" required
                       class="<?= isset($errores['correo']) ? 'invalido' : '' ?>">
                <?php if (isset($errores['correo'])): ?><span class="error"><?= e($errores['correo']) ?></span><?php endif; ?>
            </div>
            <div class="campo">
                <label for="telefono">Celular</label>
                <input type="tel" id="telefono" name="telefono" value="<?= e($telefono) ?>"
                       placeholder="3001234567" required
                       class="<?= isset($errores['telefono']) ? 'invalido' : '' ?>">
                <?php if (isset($errores['telefono'])): ?><span class="error"><?= e($errores['telefono']) ?></span><?php endif; ?>
            </div>
            <div class="campo">
                <label for="direccion">Dirección de entrega</label>
                <input type="text" id="direccion" name="direccion" value="<?= e($direccion) ?>" required
                       class="<?= isset($errores['direccion']) ? 'invalido' : '' ?>">
                <?php if (isset($errores['direccion'])): ?><span class="error"><?= e($errores['direccion']) ?></span><?php endif; ?>
            </div>
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
            <button class="btn btn-coral btn-block" type="submit">Crear cuenta</button>
        </form>

        <p class="hint" style="margin-top:1.2rem;margin-bottom:0;text-align:center;">
            ¿Ya tienes cuenta? <a href="login.php" style="color:var(--coral);font-weight:700;">Entrar</a>
            · ¿Tienes un local?
            <a href="registro_comercio.php" style="color:var(--coral);font-weight:700;">Registrar mi local</a>
        </p>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
