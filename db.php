<?php
/**
 * ARCHIVO: db.php
 * Conexión centralizada con detección automática de entorno.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detectar si estamos en Local o en Hostinger
$is_local = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1');

if ($is_local) {
    $db_host = 'localhost';
    $db_name = 'u204222083_crm_ctacte_cli';
    $db_user = 'root';
    $db_pass = '';
} else {
    $db_host = 'localhost';
    $db_name = 'u204222083_crm_ctacte_cli';
    $db_user = 'u204222083_roque';
    $db_pass = '!D^^^0iW';
}

try {
    // Usamos utf8mb4 para evitar errores con tildes del CSV
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Forzar zona horaria de Argentina en PHP y MySQL
    date_default_timezone_set('America/Argentina/Buenos_Aires');
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    // Si falla, guardamos el error para que el dashboard lo capture
    $_SESSION['db_error'] = $e->getMessage();
    // No usamos die() para que el script pueda devolver un JSON de error
}