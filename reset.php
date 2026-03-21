<?php
/**
 * ARCHIVO: reset.php
 * DESCRIPCIÓN: Resetea la contraseña del usuario administrador a '123456' y le asigna el rol 'admin'.
 */
require_once 'db.php';

// Cambia este email por el que realmente usa el administrador para iniciar sesión
$email_admin = 'admin@crm.local';  // ← AJUSTA ESTO

$nueva_clave = password_hash('123456', PASSWORD_DEFAULT);

try {
    // Actualiza usando las columnas reales: email y password
    $stmt = $pdo->prepare("UPDATE usuarios SET password = ?, rol = 'admin', activo = 1 WHERE email = ?");
    $stmt->execute([$nueva_clave, $email_admin]);

    $filas = $stmt->rowCount();
    if ($filas > 0) {
        echo "<h1>✅ Contraseña reseteada con éxito</h1>";
        echo "<p>Usuario: <b>$email_admin</b><br>Contraseña temporal: <b>123456</b></p>";
        echo "<a href='index.php'>Volver al Login</a>";
    } else {
        echo "<h1>⚠️ No se encontró ningún usuario con el email: $email_admin</h1>";
        echo "<p>Verifica que el email esté escrito correctamente en la base de datos.</p>";
        echo "<a href='index.php'>Volver al Login</a>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}