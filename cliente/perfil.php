<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../funciones.php';
require_once __DIR__ . '/../conexion.php';
exigir_cliente();
refrescar_sesion_cliente($mysqli);

$idCliente = cliente_id_sesion();
$errores = [];
$ok = false;

$nombre = (string) ($_SESSION['cliente_nombre'] ?? '');
$correo = (string) ($_SESSION['cliente_correo'] ?? '');
$telefono = (string) ($_SESSION['cliente_telefono'] ?? '');
$direccion = (string) ($_SESSION['cliente_direccion'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'perfil';

    if ($accion === 'perfil') {
        $nombre = trim($_POST['nombre'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');

        if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 100) {
            $errores['nombre'] = 'Nombre entre 3 y 100 caracteres.';
        }
        if ($telefono !== '' && !preg_match('/^3\d{9}$/', $telefono)) {
            $errores['telefono'] = 'Celular de 10 dígitos que empiece por 3.';
        }
        if ($direccion !== '' && (mb_strlen($direccion) < 5 || mb_strlen($direccion) > 150)) {
            $errores['direccion'] = 'Dirección entre 5 y 150 caracteres.';
        }

        if (count($errores) === 0) {
            $stmt = $mysqli->prepare(
                "UPDATE usuario SET nombre = ?, telefono = ?, direccion = ?
                 WHERE id_usuario = ? AND rol = 'cliente'"
            );
            $stmt->bind_param('sssi', $nombre, $telefono, $direccion, $idCliente);
            $stmt->execute();
            refrescar_sesion_cliente($mysqli);
            $_SESSION['mensaje'] = 'Información actualizada.';
            header('Location: perfil.php');
            exit;
        }
    }

    if ($accion === 'clave') {
        $actual = $_POST['clave_actual'] ?? '';
        $nueva = $_POST['clave_nueva'] ?? '';
        $nueva2 = $_POST['clave_nueva2'] ?? '';

        $stmt = $mysqli->prepare("SELECT clave FROM usuario WHERE id_usuario = ? AND rol = 'cliente' LIMIT 1");
        $stmt->bind_param('i', $idCliente);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row || !password_verify($actual, $row['clave'])) {
            $errores['clave_actual'] = 'La contraseña actual no es correcta.';
        } elseif (mb_strlen($nueva) < 6) {
            $errores['clave_nueva'] = 'La nueva contraseña debe tener al menos 6 caracteres.';
        } elseif ($nueva !== $nueva2) {
            $errores['clave_nueva2'] = 'Las contraseñas nuevas no coinciden.';
        } else {
            $hash = password_hash($nueva, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("UPDATE usuario SET clave = ? WHERE id_usuario = ? AND rol = 'cliente'");
            $stmt->bind_param('si', $hash, $idCliente);
            $stmt->execute();
            $_SESSION['mensaje'] = 'Contraseña actualizada.';
            header('Location: perfil.php');
            exit;
        }
    }
}

$mensaje = $_SESSION['mensaje'] ?? null;
unset($_SESSION['mensaje']);

$tituloPagina = 'Mi información · VeciGo';
require __DIR__ . '/../includes/header.php';
?>

<div class="contenedor">
    <div class="page-head">
        <div>
            <h1>Mi información</h1>
            <p>Datos que usamos al pedir domicilio en el barrio.</p>
        </div>
        <a class="btn btn-line" href="index.php">← Mi cuenta</a>
    </div>

    <?php if ($mensaje): ?>
        <div class="alerta alerta-exito"><?= e($mensaje) ?></div>
    <?php endif; ?>

    <div class="pedido-layout">
        <div class="card">
            <h2 style="margin-bottom:1rem;">Perfil</h2>
            <form method="post" action="perfil.php">
                <input type="hidden" name="accion" value="perfil">
                <div class="campo">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" value="<?= e($nombre) ?>" required
                           class="<?= isset($errores['nombre']) ? 'invalido' : '' ?>">
                    <?php if (isset($errores['nombre'])): ?>
                        <span class="error"><?= e($errores['nombre']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="campo">
                    <label for="correo">Correo</label>
                    <input type="email" id="correo" value="<?= e($correo) ?>" disabled>
                    <span class="hint" style="display:block;margin-top:0.35rem;">El correo es tu acceso; no se cambia aquí.</span>
                </div>
                <div class="campo">
                    <label for="telefono">Celular</label>
                    <input type="tel" id="telefono" name="telefono" value="<?= e($telefono) ?>"
                           placeholder="3001234567"
                           class="<?= isset($errores['telefono']) ? 'invalido' : '' ?>">
                    <?php if (isset($errores['telefono'])): ?>
                        <span class="error"><?= e($errores['telefono']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="campo">
                    <label for="direccion">Dirección de entrega</label>
                    <input type="text" id="direccion" name="direccion" value="<?= e($direccion) ?>"
                           placeholder="Calle 12 # 8-30 apto 101"
                           class="<?= isset($errores['direccion']) ? 'invalido' : '' ?>">
                    <?php if (isset($errores['direccion'])): ?>
                        <span class="error"><?= e($errores['direccion']) ?></span>
                    <?php endif; ?>
                </div>
                <button class="btn btn-coral" type="submit">Guardar información</button>
            </form>
        </div>

        <div class="card">
            <h2 style="margin-bottom:1rem;">Cambiar contraseña</h2>
            <form method="post" action="perfil.php">
                <input type="hidden" name="accion" value="clave">
                <div class="campo">
                    <label for="clave_actual">Contraseña actual</label>
                    <input type="password" id="clave_actual" name="clave_actual" required
                           class="<?= isset($errores['clave_actual']) ? 'invalido' : '' ?>">
                    <?php if (isset($errores['clave_actual'])): ?>
                        <span class="error"><?= e($errores['clave_actual']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="campo">
                    <label for="clave_nueva">Nueva contraseña</label>
                    <input type="password" id="clave_nueva" name="clave_nueva" required minlength="6"
                           class="<?= isset($errores['clave_nueva']) ? 'invalido' : '' ?>">
                    <?php if (isset($errores['clave_nueva'])): ?>
                        <span class="error"><?= e($errores['clave_nueva']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="campo">
                    <label for="clave_nueva2">Repetir nueva</label>
                    <input type="password" id="clave_nueva2" name="clave_nueva2" required minlength="6"
                           class="<?= isset($errores['clave_nueva2']) ? 'invalido' : '' ?>">
                    <?php if (isset($errores['clave_nueva2'])): ?>
                        <span class="error"><?= e($errores['clave_nueva2']) ?></span>
                    <?php endif; ?>
                </div>
                <button class="btn btn-line" type="submit">Actualizar contraseña</button>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
