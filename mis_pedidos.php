<?php
/**
 * Compatibilidad: redirige al panel cliente o al login.
 */
require_once __DIR__ . '/funciones.php';
iniciar_sesion();

if (cliente_logueado()) {
    header('Location: cliente/pedidos.php');
    exit;
}

header('Location: login.php');
exit;
