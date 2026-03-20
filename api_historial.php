<?php
/**
 * ARCHIVO: api_historial.php
 * Descripción: Lee el timeline usando LEGAJO como filtro principal.
 * Devuelve también estado, monto_promesa y fecha_promesa para mostrar
 * el detalle completo en cada tarjeta del historial.
 */
require_once 'db.php';

if (!isset($_SESSION['user_id'])) exit;

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

$id_interno   = (int)($_GET['id']    ?? 0);
$legajo_param = trim($_GET['legajo'] ?? '');

// Obtenemos legajo desde la BD; si no lo encontramos usamos el parámetro GET
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

    // ── BÚSQUEDA PRINCIPAL: por LEGAJO (nunca por cliente_id solo) ─────────
    if (!empty($legajo)) {
        $stmt = $pdo->prepare(
            "SELECT 
                g.id,
                g.fecha_gestion   AS fecha,
                g.observaciones   AS observacion,
                g.estado          AS estado,
                g.monto_promesa   AS monto_promesa,
                g.fecha_promesa   AS fecha_promesa,
                IFNULL(u.nombre, 'Sistema') AS operador
             FROM gestiones_historial g
             LEFT JOIN usuarios u ON g.usuario_id = u.id
             WHERE g.legajo = :legajo
             ORDER BY g.fecha_gestion DESC"
        );
        $stmt->execute([':legajo' => $legajo]);
        $todas = $stmt->fetchAll();
    }

    // ── Formateo de seguridad ──────────────────────────────────────────────
    foreach ($todas as &$r) {
        // Fecha de gestión
        if (empty($r['fecha']) || $r['fecha'] === '0000-00-00 00:00:00') {
            $r['fecha'] = date('Y-m-d H:i:s');
        }
        if (strpos($r['fecha'], ' ') === false) {
            $r['fecha'] .= ' 00:00:00';
        }
        // Fecha promesa legible (la formatea el JS, solo aseguramos que no sea null)
        $r['fecha_promesa'] = $r['fecha_promesa'] ?? '';
        $r['monto_promesa'] = $r['monto_promesa'] ?? 0;
        $r['estado']        = $r['estado']        ?? '';
    }
    unset($r);

    echo json_encode($todas);

} catch (Exception $e) {
    echo json_encode([[
        'id'          => 0,
        'fecha'       => date('Y-m-d H:i:s'),
        'operador'    => 'SISTEMA',
        'observacion' => '🚨 ERROR SQL: ' . $e->getMessage(),
        'estado'      => '',
        'monto_promesa' => 0,
        'fecha_promesa' => ''
    ]]);
}
?>