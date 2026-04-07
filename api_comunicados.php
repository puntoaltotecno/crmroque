<?php
/**
 * ARCHIVO: api_comunicados.php
 */
require_once 'db.php';

if (!isset($_SESSION['user_id'])) exit;

header('Content-Type: application/json');

$action = $_GET['action'] ?? ($_POST['action'] ?? 'get');
$rol = $_SESSION['user_rol'] ?? 'operador';
$can_manage = ($rol === 'admin' || $rol === 'colaborador');
$user_id = (int)$_SESSION['user_id'];

try {
    if ($action === 'get') {
        // Trae el último aviso que esté activo y que sea: 
        // a) Para todos (usuario_destino_id IS NULL) 
        // b) O específicamente para este usuario (usuario_destino_id = user_id)
        $stmt = $pdo->prepare("
            SELECT * FROM comunicados 
            WHERE activo = 1 
            AND (usuario_destino_id IS NULL OR usuario_destino_id = 0 OR usuario_destino_id = ?) 
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $comunicado = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $comunicado]);
    } 
    elseif ($action === 'save' && $can_manage) {
        $mensaje = trim($_POST['mensaje'] ?? '');
        $destino_id = isset($_POST['usuario_destino_id']) && (int)$_POST['usuario_destino_id'] > 0 ? (int)$_POST['usuario_destino_id'] : null;
        
        if(empty($mensaje)) throw new Exception("El mensaje no puede estar vacío");
        
        // Si el aviso es PARA TODOS, desactivamos los anteriores PARA TODOS.
        // Si es PRIVADO, desactivamos los anteriores PRIVADOS para esa persona.
        if ($destino_id === null) {
            $pdo->query("UPDATE comunicados SET activo = 0 WHERE usuario_destino_id IS NULL OR usuario_destino_id = 0");
        } else {
            $stmt_off = $pdo->prepare("UPDATE comunicados SET activo = 0 WHERE usuario_destino_id = ?");
            $stmt_off->execute([$destino_id]);
        }
        
        // Insertamos el nuevo
        $stmt = $pdo->prepare("INSERT INTO comunicados (usuario_destino_id, mensaje, activo, fecha_creacion) VALUES (?, ?, 1, NOW())");
        $stmt->execute([$destino_id, $mensaje]);
        
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'delete' && $can_manage) {
        // Apagar todos los avisos activos
        $pdo->query("UPDATE comunicados SET activo = 0");
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Acción no permitida.");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>