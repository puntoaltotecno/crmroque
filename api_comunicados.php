<?php
/**
 * ARCHIVO: api_comunicados.php
 * Soluciona la visibilidad para operadores y activa Short Polling.
 */
ob_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Sesión expirada']);
    exit;
}

ob_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$action = $_GET['action'] ?? ($_POST['action'] ?? 'get');
$rol = $_SESSION['user_rol'] ?? 'operador';
$can_manage = ($rol === 'admin' || $rol === 'colaborador');
$user_id = (int)$_SESSION['user_id'];

try {
    if ($action === 'get') {
        // La consulta ahora es más estricta con los paréntesis para asegurar que los operadores vean lo global (NULL o 0)
        $stmt = $pdo->prepare("
            SELECT id, mensaje, fecha_creacion, usuario_destino_id 
            FROM comunicados 
            WHERE activo = 1 
            AND (usuario_destino_id IS NULL OR usuario_destino_id = 0 OR usuario_destino_id = :uid) 
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([':uid' => $user_id]);
        $comunicado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $comunicado], JSON_UNESCAPED_UNICODE);
    } 
    elseif ($action === 'save' && $can_manage) {
        $mensaje = trim($_POST['mensaje'] ?? '');
        $destino_raw = $_POST['usuario_destino_id'] ?? null;
        
        // Normalización: Si es 0 o vacío, es PARA TODOS (NULL)
        $destino_id = ($destino_raw !== null && (int)$destino_raw > 0) ? (int)$destino_raw : null;
        
        if (empty($mensaje)) throw new Exception("Mensaje vacío");

        // Desactivar previos del mismo tipo para no saturar
        if ($destino_id === null) {
            $pdo->query("UPDATE comunicados SET activo = 0 WHERE usuario_destino_id IS NULL OR usuario_destino_id = 0");
        } else {
            $stmt_off = $pdo->prepare("UPDATE comunicados SET activo = 0 WHERE usuario_destino_id = ?");
            $stmt_off->execute([$destino_id]);
        }
        
        $stmt = $pdo->prepare("INSERT INTO comunicados (usuario_destino_id, mensaje, activo, fecha_creacion) VALUES (?, ?, 1, NOW())");
        $stmt->execute([$destino_id, $mensaje]);
        
        echo json_encode(['success' => true]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}