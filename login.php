<?php
/**
 * ARCHIVO: login.php
 * DESCRIPCIÓN: Procesa el inicio de sesión, verifica credenciales contra la tabla 'usuarios' y establece sesión.
 */
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$email = $_POST['usuario'] ?? '';   // El campo del formulario se llama "usuario"
$clave = $_POST['clave'] ?? '';

if (empty($email) || empty($clave)) {
    echo json_encode(['success' => false, 'message' => 'Completa todos los campos']);
    exit;
}

try {
    // Buscar por email (coincide con la columna 'email')
    $stmt = $pdo->prepare("SELECT id, nombre, email, password, rol, activo FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['activo'] == 1 && password_verify($clave, $user['password'])) {
        // Iniciar sesión
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['nombre'];
        $_SESSION['user_rol']  = $user['rol'];
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario o contraseña incorrectos']);
    }
} catch (PDOException $e) {
    // En producción no mostrar el mensaje de error real
    echo json_encode(['success' => false, 'message' => 'Error en el servidor']);
}