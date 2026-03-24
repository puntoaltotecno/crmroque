<?php
/**
 * ARCHIVO: db.php
 * Configura la conexión a BD con seguridad para producción.
 */
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Zona horaria Argentina (UTC-3, sin horario de verano) ──────────────────
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Detección automática de entorno
$is_local = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1');
if ($is_local) {
    // Configuración XAMPP Local
    $db_host = 'localhost';
    $db_name = 'u204222083_crm_ctacte_cli'; // En local puedes llamarla como quieras, pero mantengo tu nombre
    $db_user = 'root'; 
    $db_pass = '';
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // Configuración Hostinger Producción
    $db_host = 'localhost'; // En Hostinger casi siempre es localhost
    $db_name = 'u204222083_crm_ctacte_cli';
    $db_user = 'u204222083_roque';
    $db_pass = '!D^^^0iW';
    
    // Apagamos errores visuales para seguridad
    error_reporting(0);
    ini_set('display_errors', 0);
}
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    // Modo de errores estricto interno, pero capturado por el catch
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // ── Sincronizar zona horaria de MySQL (NOW(), TIMESTAMP, etc.) ──────────
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    if ($is_local) {
        die("❌ Error de conexión BD: " . $e->getMessage());
    } else {
        // En Hostinger no mostramos la contraseña ni el error real
        error_log("Error BD: " . $e->getMessage()); // Se guarda en el error_log del servidor
        die("Error de conexión con el servidor. Por favor, intente más tarde.");
    }
}
?>