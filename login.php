<?php
/**
 * ARCHIVO: login.php
 * Descripción: Procesa el ingreso y responde en formato JSON.
 */
require_once 'db.php';

if (ob_get_length()) ob_clean();
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $u = trim($_POST['usuario'] ?? '');
        $p = trim($_POST['clave'] ?? '');

        if (empty($u) || empty($p)) {
            echo json_encode(['success' => false, 'message' => 'Complete todos los campos.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$u]);
        $user = $stmt->fetch();

        if ($user) {
            $hash_bd = $user['password'] ?? $user['clave'] ?? null;
            if ($hash_bd && password_verify($p, $hash_bd)) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nombre'];
                $_SESSION['user_rol'] = $user['rol'];
                echo json_encode(['success' => true]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
exit;