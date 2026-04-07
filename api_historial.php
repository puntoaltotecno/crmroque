<?php
/**
 * ARCHIVO: api_historial.php
 */
require_once 'db.php';

if (!isset($_SESSION['user_id'])) exit;

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

$id_interno   = (int)($_GET['id']    ?? 0);
$legajo_param = trim($_GET['legajo'] ?? '');
$ver_ocultas  = (isset($_GET['ver_ocultas']) && $_GET['ver_ocultas'] === '1') ? 1 : 0;
$rol_usuario  = $_SESSION['user_rol'] ?? 'operador';
$can_see_hidden = ($rol_usuario === 'admin' || $rol_usuario === 'colaborador');

// Un operador nunca puede ver las ocultas
if (!$can_see_hidden) {
    $ver_ocultas = 0;
}

$legajo = '';
try {
    $s = $pdo->prepare("SELECT legajo FROM clientes WHERE id = ?");
    $s->execute([$id_interno]);
    $legajo = trim($s->fetchColumn() ?? '');
} catch (Exception $ex) {}

if (empty($legajo) && !empty($legajo_param)) {
    $legajo = $legajo_param;
}

try {
    $todas = [];
    if (!empty($legajo)) {
        $where = "g.legajo = :legajo";
        
        // Si no activó "ver ocultas", filtramos
        if (!$ver_ocultas) {
            $where .= " AND (g.oculta IS NULL OR g.oculta = 0)";
        }

        $stmt = $pdo->prepare(
            "SELECT 
                g.id,
                g.fecha_gestion   AS fecha,
                g.observaciones   AS observacion,
                g.estado          AS estado,
                g.monto_promesa   AS monto_promesa,
                g.fecha_promesa   AS fecha_promesa,
                IFNULL(u.nombre, 'Sistema') AS operador,
                g.usuario_id,
                g.intentos,
                g.oculta
             FROM gestiones_historial g
             LEFT JOIN usuarios u ON g.usuario_id = u.id
             WHERE $where
             ORDER BY g.fecha_gestion DESC"
        );
        $stmt->execute([':legajo' => $legajo]);
        $todas = $stmt->fetchAll();
    }

    foreach ($todas as &$r) {
        if (empty($r['fecha']) || $r['fecha'] === '0000-00-00 00:00:00') {
            $r['fecha'] = date('Y-m-d H:i:s');
        }
        if (strpos($r['fecha'], ' ') === false) {
            $r['fecha'] .= ' 00:00:00';
        }
        $r['fecha_promesa'] = $r['fecha_promesa'] ?? '';
        $r['monto_promesa'] = $r['monto_promesa'] ?? 0;
        $r['estado']        = $r['estado']        ?? '';
    }
    unset($r);

    echo json_encode($todas);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>