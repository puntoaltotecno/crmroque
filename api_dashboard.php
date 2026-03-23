<?php
/**
 * ARCHIVO: api_dashboard.php
 * Extrae las métricas globales, estados de cartera y rendimiento por operador.
 */
require_once 'db.php';

// Validar sesión y rol (Solo Admin y Colaborador)
if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] !== 'admin' && $_SESSION['user_rol'] !== 'colaborador')) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

try {
    // 1. ── MÉTRICAS GLOBALES (RESUMEN GENERAL) ──
    $sql_resumen = "SELECT 
        (SELECT COUNT(*) FROM asignaciones) as total_asignados,
        (SELECT COUNT(DISTINCT legajo) FROM gestiones_historial) as total_gestionados,
        (SELECT COUNT(DISTINCT legajo) FROM gestiones_historial WHERE estado = 'promesa') as total_promesas
    ";
    $stmt_resumen = $pdo->query($sql_resumen);
    $resumen = $stmt_resumen->fetch(PDO::FETCH_ASSOC);

    // Calcular Cobertura (% de asignados que ya fueron gestionados al menos una vez)
    $cobertura = 0;
    if ($resumen['total_asignados'] > 0) {
        $cobertura = round(($resumen['total_gestionados'] / $resumen['total_asignados']) * 100);
    }
    // Evitar que la cobertura pase del 100% por desfases lógicos
    $resumen['cobertura'] = $cobertura > 100 ? 100 : $cobertura; 

    // 2. ── ESTADOS DE LA CARTERA ──
    // Obtenemos el estado actual de cada legajo (la última gestión)
    $sql_estados = "SELECT 
            COALESCE(u.estado, 'sin_gestion') AS estado_actual,
            COUNT(c.legajo) AS cantidad
        FROM clientes c
        LEFT JOIN (
            SELECT g.legajo, g.estado
            FROM gestiones_historial g
            INNER JOIN (
                SELECT legajo, MAX(id) as max_id
                FROM gestiones_historial
                GROUP BY legajo
            ) max_g ON g.id = max_g.max_id
        ) u ON c.legajo = u.legajo
        GROUP BY estado_actual";
        
    $stmt_estados = $pdo->query($sql_estados);
    $estados_raw = $stmt_estados->fetchAll(PDO::FETCH_ASSOC);
    
    // Convertimos a un diccionario clave => valor para facilitar el uso en JS
    $estados = [];
    foreach($estados_raw as $row) {
        $estados[$row['estado_actual']] = (int)$row['cantidad'];
    }

    // 3. ── RENDIMIENTO POR OPERADOR ──
    $sql_ops = "SELECT 
                u.id, 
                u.nombre, 
                (SELECT COUNT(*) FROM asignaciones a WHERE a.usuario_id = u.id) as total_asignados,
                (SELECT COUNT(DISTINCT h.legajo) FROM gestiones_historial h WHERE h.usuario_id = u.id) as clientes_gestionados,
                (SELECT COUNT(DISTINCT h2.legajo) FROM gestiones_historial h2 WHERE h2.usuario_id = u.id AND h2.estado = 'promesa') as promesas_logradas
            FROM usuarios u
            WHERE u.rol = 'operador' OR u.rol = 'colaborador'
            ORDER BY total_asignados DESC";
            
    $stmt_ops = $pdo->query($sql_ops);
    $data = $stmt_ops->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true, 
        'resumen' => $resumen, 
        'estados' => $estados,
        'data' => $data
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al cargar métricas: ' . $e->getMessage()]);
}
?>