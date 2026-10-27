<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/conexion.php';
iniciar_sesion();

if (admin_logueado()) {
    header('Location: admin/index.php');
    exit;
}
if (comercio_logueado()) {
    header('Location: comercio/index.php');
    exit;
}
if (cliente_logueado()) {
    header('Location: cliente/index.php');
    exit;
}

$error = '';
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = mb_strtolower(trim($_POST['correo'] ?? ''));
    $clave = $_POST['clave'] ?? '';

    if ($correo === '' || $clave === '') {
        $error = 'Escribe correo y contraseña.';
    } else {
        $stmt = $mysqli->prepare(
            "SELECT u.id_usuario, u.nombre, u.correo, u.telefono, u.direccion, u.clave, u.rol,
                    u.id_comercio, c.nombre AS local, c.activo
             FROM usuario u
             LEFT JOIN comercio c ON c.id_comercio = u.id_comercio
             WHERE u.correo = ? OR u.usuario = ?
             LIMIT 1"
        );
        $stmt->bind_param('ss', $correo, $correo);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row && password_verify($clave, $row['clave'])) {
            if ($row['rol'] === 'comercio' && (int) ($row['activo'] ?? 0) !== 1) {
                $error = 'Este comercio no está activo. Contacta al admin del barrio.';
            } else {
                $destino = iniciar_sesion_por_rol($row);
                if ($destino !== '') {
                    header('Location: ' . $destino);
                    exit;
                }
                $error = 'Tu cuenta no tiene un panel asignado.';
            }
        } else {
            $error = 'Correo o contraseña incorrectos.';
        }
    }
}

$tituloPagina = 'Entrar · VeciGo';
require __DIR__ . '/includes/header.php';
?>

<div class="login-wrap">
    <div class="login-card">
        <h1>Entrar a VeciGo</h1>
        <p class="hint">Un solo acceso: el correo define si eres cliente, comercio o admin.</p>

        <div class="demo-box">
            <strong>Demo presentación</strong><br>
            Cliente: <code>carlos@vecigo.co</code> · <code>cliente2024</code><br>
            Comercio: <code>donarosa@vecigo.co</code> · <code>comercio2024</code><br>
            Admin: <code>admin@vecigo.co</code> · <code>vecigo2024</code>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alerta alerta-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php" autocomplete="on">
            <div class="campo">
                <label for="correo">Correo</label>
                <input type="email" id="correo" name="correo" value="<?= e($correo) ?>"
                       placeholder="tu@correo.com" required autofocus autocomplete="username">
            </div>
            <div class="campo">
                <label for="clave">Contraseña</label>
                <input type="password" id="clave" name="clave" required autocomplete="current-password">
            </div>
            <button class="btn btn-coral btn-block" type="submit">Entrar</button>
        </form>

        <p class="hint" style="margin-top:1.2rem;margin-bottom:0;text-align:center;">
            ¿Cliente nuevo?
            <a href="registro.php" style="color:var(--coral);font-weight:700;">Crear cuenta</a>
            · ¿Tienes un local?
            <a href="registro_comercio.php" style="color:var(--coral);font-weight:700;">Registrar mi local</a>
        </p>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
