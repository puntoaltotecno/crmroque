<?php
/**
 * ARCHIVO: api_asignar.php
 * Descripción: Permite al admin asignar o desasignar un legajo a un operador manualmente desde la ficha 360.
 */
require_once 'db.php';

// Verificación de seguridad: Solo el Administrador puede hacer esto
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No tienes permisos de Administrador.']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $legajo = trim($_POST['legajo'] ?? '');
    $usuario_id = trim($_POST['usuario_id'] ?? '');

    if (empty($legajo)) {
        echo json_encode(['success' => false, 'message' => 'El cliente no tiene un legajo válido para ser asignado.']);
        exit;
    }

    try {
        if (empty($usuario_id)) {
            // Si elige "Sin Asignar", borramos el registro de la tabla
            $stmt = $pdo->prepare("DELETE FROM asignaciones WHERE legajo = ?");
            $stmt->execute([$legajo]);
        } else {
            // Si elige a un Operador, lo asignamos (o actualizamos si ya tenía otro)
            $stmt = $pdo->prepare("INSERT INTO asignaciones (legajo, usuario_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE usuario_id = VALUES(usuario_id)");
            $stmt->execute([$legajo, $usuario_id]);
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error de BD: ' . $e->getMessage()]);
    }
}
?>