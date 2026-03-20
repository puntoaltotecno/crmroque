<?php
/**
 * ARCHIVO: db.php
 * Descripción: Configura la conexión a la base de datos con detección de entorno.
 */

ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detectamos entorno (localhost vs Hostinger)
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
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}