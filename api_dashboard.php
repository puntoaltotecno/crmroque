<?php
/**
 * ARCHIVO: api_dashboard.php
 * Versión robusta para Hostinger.
 */

// 1. Limpieza absoluta: Prevenir cualquier salida previa al JSON
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once 'db.php';

    // Si hubo error de conexión en db.php, lo reportamos aquí
    if (!isset($pdo)) {
        throw new Exception("Error de conexión a BD: " . ($_SESSION['db_error'] ?? 'Desconocido'));
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Sesión expirada.");
    }

    // --- Consultas ---
    $res = $pdo->query("SELECT 
        (SELECT COUNT(*) FROM asignaciones) as total_asignados,
        (SELECT COUNT(DISTINCT legajo) FROM gestiones_historial) as total_gestionados,
        (SELECT COUNT(DISTINCT legajo) FROM gestiones_historial WHERE estado = 'promesa') as total_promesas
    ")->fetch();

    $cobertura = ($res['total_asignados'] > 0) ? round(($res['total_gestionados'] / $res['total_asignados']) * 100) : 0;

    $estados_raw = $pdo->query("SELECT IFNULL(u.estado_actual, 'sin_gestion') as estado_actual, COUNT(c.id) as cantidad
        FROM clientes c
        LEFT JOIN (
            SELECT g.legajo, g.estado as estado_actual FROM gestiones_historial g
            JOIN (SELECT MAX(id) as max_id FROM gestiones_historial GROUP BY legajo) max_g ON g.id = max_g.max_id
        ) u ON c.legajo = u.legajo GROUP BY estado_actual")->fetchAll();
    
    $estados = [];
    foreach($estados_raw as $r) { $estados[$r['estado_actual']] = (int)$r['cantidad']; }

    $ops = $pdo->query("SELECT u.nombre, 
        (SELECT COUNT(*) FROM asignaciones a WHERE a.usuario_id = u.id) as total_asignados,
        (SELECT COUNT(DISTINCT h.legajo) FROM gestiones_historial h WHERE h.usuario_id = u.id) as clientes_gestionados,
        (SELECT COUNT(h3.id) FROM gestiones_historial h3 WHERE h3.usuario_id = u.id) as total_gestiones,
        (SELECT COUNT(DISTINCT h2.legajo) FROM gestiones_historial h2 WHERE h2.usuario_id = u.id AND h2.estado = 'promesa') as promesas_logradas
        FROM usuarios u WHERE u.rol IN ('operador', 'colaborador') ORDER BY total_asignados DESC")->fetchAll();

    $sucursales = $pdo->query("SELECT 
            c_agg.sucursal_nombre,
            c_agg.total_clientes,
            c_agg.deuda_en_calle,
            IFNULL(g_agg.total_gestiones, 0) as total_gestiones
        FROM 
            (SELECT IFNULL(NULLIF(TRIM(sucursal), ''), 'Central') as sucursal_nombre, 
                    COUNT(id) as total_clientes, 
                    SUM(total_vencido) as deuda_en_calle
             FROM clientes 
             GROUP BY sucursal_nombre) c_agg
        LEFT JOIN 
             (SELECT IFNULL(NULLIF(TRIM(c.sucursal), ''), 'Central') as sucursal_nombre, 
                     COUNT(g.id) as total_gestiones
              FROM gestiones_historial g 
              JOIN clientes c ON g.legajo = c.legajo 
              WHERE g.oculta = 0 OR g.oculta IS NULL
              GROUP BY sucursal_nombre) g_agg
        ON c_agg.sucursal_nombre = g_agg.sucursal_nombre
        ORDER BY c_agg.deuda_en_calle DESC")->fetchAll();

    // Feed de gestiones (Filtro seguro para Hostinger)
    $feed = [];
    try {
        $feed = $pdo->query("SELECT g.estado as feed_estado, g.observaciones as feed_obs, g.fecha_gestion as feed_fecha,
                            u.nombre as feed_operador, c.legajo, c.razon_social
                            FROM gestiones_historial g
                            JOIN clientes c ON g.legajo = c.legajo
                            LEFT JOIN usuarios u ON g.usuario_id = u.id
                            WHERE g.oculta = 0 OR g.oculta IS NULL 
                            ORDER BY g.id DESC LIMIT 10")->fetchAll();
    } catch(Exception $e) {
        $feed = $pdo->query("SELECT g.estado as feed_estado, g.observaciones as feed_obs, g.fecha_gestion as feed_fecha,
                            u.nombre as feed_operador, c.legajo, c.razon_social
                            FROM gestiones_historial g
                            JOIN clientes c ON g.legajo = c.legajo
                            LEFT JOIN usuarios u ON g.usuario_id = u.id
                            ORDER BY g.id DESC LIMIT 10")->fetchAll();
    }

    $out = [
        'success' => true,
        'resumen' => [
            'total_asignados' => $res['total_asignados'],
            'total_gestionados' => $res['total_gestionados'],
            'total_promesas' => $res['total_promesas'],
            'cobertura' => $cobertura
        ],
        'estados' => $estados,
        'data' => $ops,
        'sucursales' => $sucursales,
        'ultimas_gestiones' => $feed
    ];

    // Limpiamos basura del buffer y enviamos
    ob_clean();
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
ob_end_flush();