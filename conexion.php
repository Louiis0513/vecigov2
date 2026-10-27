<?php
// PASO 1: conexión a la base de datos "vecigo"
// Este archivo se incluye en TODAS las páginas que usan la base de datos:
//     require_once __DIR__ . '/conexion.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host      = '127.0.0.1';
$usuario   = 'root';
$password  = '';          // en XAMPP suele ir vacío
$baseDatos = 'vecigo';
$puerto    = 3306;

try {
    $mysqli = new mysqli($host, $usuario, $password, $baseDatos, $puerto);
    $mysqli->set_charset('utf8mb4');   // tildes y ñ correctas
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Error de conexión a la base de datos: ' . $e->getMessage());
}
