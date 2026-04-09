<?php
/**
 * ARCHIVO: api_reportes.php
 * DESCRIPCIÓN: Módulo de reportes analíticos de rendimiento de operadores
 * Soporta: diario, semanal, mensual, rango personalizado
 */
require_once 'db.php';
ob_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Sesión expirada']);
    exit;
}

// Solo Admin y Colaborador pueden ver reportes
$rol = $_SESSION['user_rol'];
if ($rol !== 'admin' && $rol !== 'colaborador') {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Acceso restringido']);
    exit;
}

ob_clean();

try {
    $action = $_GET['action'] ?? 'resumen_diario';
    $fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-7 days'));
    $fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
    $operador_id = isset($_GET['operador_id']) ? (int)$_GET['operador_id'] : 0;

    // ══════════════════════════════════════════════════════════════════════════
    // 1. RESUMEN DIARIO — Gestiones por día, últimos 7 días
    // ══════════════════════════════════════════════════════════════════════════
    if ($action === 'resumen_diario') {
        $sql = "
            SELECT 
                DATE(g.fecha_gestion) as fecha,
                COUNT(g.id) as total_gestiones,
                COUNT(DISTINCT g.usuario_id) as operadores_activos,
                SUM(CASE WHEN g.estado = 'promesa' THEN 1 ELSE 0 END) as promesas,
                SUM(CASE WHEN g.estado = 'al_dia' THEN 1 ELSE 0 END) as al_dia,
                SUM(CASE WHEN g.estado = 'no_responde' THEN 1 ELSE 0 END) as no_responde,
                SUM(CASE WHEN g.estado = 'no_corresponde' THEN 1 ELSE 0 END) as no_corresponde,
                SUM(CASE WHEN g.estado = 'llamar' THEN 1 ELSE 0 END) as llamar,
                SUM(CASE WHEN g.estado = 'numero_baja' THEN 1 ELSE 0 END) as numero_baja,
                SUM(CASE WHEN g.estado = 'carta' THEN 1 ELSE 0 END) as carta,
                SUM(CASE WHEN g.estado = 'otro' THEN 1 ELSE 0 END) as otro
            FROM gestiones_historial g
            WHERE DATE(g.fecha_gestion) BETWEEN :desde AND :hasta
            GROUP BY DATE(g.fecha_gestion)
            ORDER BY fecha DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':desde' => $fecha_desde, ':hasta' => $fecha_hasta]);
        $datos = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $datos, 'titulo' => 'Resumen Diario de Gestiones']);
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 2. RANKING DE OPERADORES — Quién más gestiones hizo
    // ══════════════════════════════════════════════════════════════════════════
    if ($action === 'ranking_operadores') {
        $sql = "
            SELECT 
                u.id,
                u.nombre,
                u.rol,
                COUNT(g.id) as total_gestiones,
                COUNT(DISTINCT g.legajo) as clientes_gestionados,
                SUM(CASE WHEN g.estado = 'promesa' THEN 1 ELSE 0 END) as promesas_logradas,
                SUM(CASE WHEN g.estado = 'no_responde' THEN 1 ELSE 0 END) as no_responde,
                SUM(CASE WHEN g.estado = 'no_corresponde' THEN 1 ELSE 0 END) as no_corresponde,
                SUM(CASE WHEN g.estado = 'llamar' THEN 1 ELSE 0 END) as llamar,
                SUM(CASE WHEN g.estado = 'numero_baja' THEN 1 ELSE 0 END) as numero_baja,
                SUM(CASE WHEN g.estado = 'carta' THEN 1 ELSE 0 END) as carta,
                SUM(CASE WHEN g.estado = 'otro' THEN 1 ELSE 0 END) as otro,
                MIN(g.fecha_gestion) as primera_gestion,
                MAX(g.fecha_gestion) as ultima_gestion,
                /* Al día: cuenta clientes asignados al operador cuya última gestión es 'al_dia'.
                   Se usa la tabla asignaciones porque clientes no tiene operador_id directamente. */
                (SELECT COUNT(*)
                 FROM asignaciones asg
                 JOIN gestiones_historial gh ON asg.legajo = gh.legajo
                 WHERE asg.usuario_id = u.id
                   AND gh.id = (SELECT MAX(id) FROM gestiones_historial WHERE legajo = asg.legajo)
                   AND gh.estado = 'al_dia'
                ) as clientes_al_dia
            FROM usuarios u
            LEFT JOIN gestiones_historial g ON u.id = g.usuario_id 
                AND DATE(g.fecha_gestion) BETWEEN :desde AND :hasta
            WHERE u.rol = 'operador' AND u.activo = 1 AND u.id != 42
            GROUP BY u.id, u.nombre, u.rol
            ORDER BY total_gestiones DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':desde' => $fecha_desde, ':hasta' => $fecha_hasta]);
        $datos = $stmt->fetchAll();
        
        // Calcular métrica de efectividad
        foreach ($datos as &$op) {
            $op['efectividad_pct'] = $op['clientes_gestionados'] > 0 
                ? round(($op['promesas_logradas'] / $op['clientes_gestionados']) * 100) 
                : 0;
            $op['tasa_conversion_al_dia'] = $op['clientes_gestionados'] > 0 
                ? round(($op['clientes_al_dia'] / $op['clientes_gestionados']) * 100) 
                : 0;
        }
        
        echo json_encode(['success' => true, 'data' => $datos, 'titulo' => 'Ranking de Operadores por Gestiones']);
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 3. DETALLES POR OPERADOR — Vista profunda de un operador
    // ══════════════════════════════════════════════════════════════════════════
    if ($action === 'detalle_operador') {
        if ($operador_id === 0) {
            echo json_encode(['success' => false, 'error' => 'Especifica un operador_id']);
            exit;
        }

        // Datos generales del operador
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$operador_id]);
        $operador = $stmt->fetch();
        
        if (!$operador) {
            echo json_encode(['success' => false, 'error' => 'Operador no encontrado']);
            exit;
        }

        // Gestiones por estado
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(g.estado, 'sin_gestion') as estado,
                COUNT(g.id) as cantidad,
                COUNT(DISTINCT g.legajo) as clientes_unicos
            FROM gestiones_historial g
            WHERE g.usuario_id = ? AND DATE(g.fecha_gestion) BETWEEN ? AND ?
            GROUP BY g.estado
            ORDER BY cantidad DESC
        ");
        $stmt->execute([$operador_id, $fecha_desde, $fecha_hasta]);
        $por_estado = $stmt->fetchAll();

        // Gestiones por día (últimos 7 días)
        $stmt = $pdo->prepare("
            SELECT 
                DATE(g.fecha_gestion) as fecha,
                COUNT(g.id) as cantidad,
                COUNT(DISTINCT g.legajo) as clientes_distintos
            FROM gestiones_historial g
            WHERE g.usuario_id = ? AND DATE(g.fecha_gestion) BETWEEN ? AND ?
            GROUP BY DATE(g.fecha_gestion)
            ORDER BY fecha DESC
        ");
        $stmt->execute([$operador_id, $fecha_desde, $fecha_hasta]);
        $por_dia = $stmt->fetchAll();

        // Clientes asignados vs gestionados
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT a.legajo) as asignados,
                COUNT(DISTINCT g.legajo) as gestionados,
                COUNT(DISTINCT CASE WHEN g.estado = 'al_dia' THEN g.legajo END) as al_dia,
                COUNT(DISTINCT CASE WHEN g.estado = 'promesa' THEN g.legajo END) as en_promesa,
                COUNT(DISTINCT CASE WHEN g.estado IS NULL THEN g.legajo END) as sin_gestion
            FROM asignaciones a
            LEFT JOIN gestiones_historial g ON a.legajo = g.legajo 
                AND g.usuario_id = a.usuario_id
                AND DATE(g.fecha_gestion) BETWEEN ? AND ?
            WHERE a.usuario_id = ?
        ");
        $stmt->execute([$fecha_desde, $fecha_hasta, $operador_id]);
        $cartera = $stmt->fetch();

        echo json_encode([
            'success' => true,
            'operador' => $operador,
            'por_estado' => $por_estado,
            'por_dia' => $por_dia,
            'cartera' => $cartera,
            'fecha_desde' => $fecha_desde,
            'fecha_hasta' => $fecha_hasta
        ]);
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 4. PRODUCTIVIDAD COMPARADA — Operadores activos vs inactivos
    // ══════════════════════════════════════════════════════════════════════════
    if ($action === 'productividad') {
        $sql = "
            SELECT 
                u.nombre as operador,
                u.rol,
                COALESCE(COUNT(DISTINCT DATE(g.fecha_gestion)), 0) as dias_activos,
                COALESCE(COUNT(g.id), 0) as total_gestiones,
                COALESCE(COUNT(DISTINCT g.legajo), 0) as clientes_distintos,
                COALESCE(ROUND(COUNT(g.id) / NULLIF(COUNT(DISTINCT DATE(g.fecha_gestion)), 0), 2), 0) as promedio_gestiones_por_dia,
                COALESCE(SUM(CASE WHEN g.estado = 'promesa' THEN 1 ELSE 0 END), 0) as promesas,
                COALESCE(SUM(CASE WHEN g.estado = 'al_dia' THEN 1 ELSE 0 END), 0) as al_dia,
                COALESCE(SUM(CASE WHEN g.estado = 'no_responde' THEN 1 ELSE 0 END), 0) as no_responde,
                COALESCE(SUM(CASE WHEN g.estado = 'no_corresponde' THEN 1 ELSE 0 END), 0) as no_corresponde,
                MAX(g.fecha_gestion) as ultima_gestion
            FROM usuarios u
            LEFT JOIN gestiones_historial g ON u.id = g.usuario_id 
                AND DATE(g.fecha_gestion) BETWEEN :desde AND :hasta
            WHERE u.rol = 'operador' AND u.activo = 1 AND u.id != 42
            GROUP BY u.id, u.nombre, u.rol
            ORDER BY total_gestiones DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':desde' => $fecha_desde, ':hasta' => $fecha_hasta]);
        $datos = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $datos, 'titulo' => 'Productividad Operativa']);
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 5. EFECTIVIDAD (Al Día) — Quién mejor logra cerrar casos
    // ══════════════════════════════════════════════════════════════════════════
    if ($action === 'efectividad_al_dia') {
        $sql = "
            SELECT 
                u.id,
                u.nombre,
                COUNT(DISTINCT g.legajo) as clientes_gestionados,
                SUM(CASE WHEN g.estado = 'al_dia' THEN 1 ELSE 0 END) as al_dia_logrados,
                ROUND(100 * SUM(CASE WHEN g.estado = 'al_dia' THEN 1 ELSE 0 END) / NULLIF(COUNT(DISTINCT g.legajo), 0), 2) as tasa_al_dia_pct,
                SUM(CASE WHEN g.estado = 'promesa' THEN 1 ELSE 0 END) as promesas,
                SUM(CASE WHEN g.estado = 'no_responde' THEN 1 ELSE 0 END) as no_responde,
                SUM(CASE WHEN g.estado = 'no_corresponde' THEN 1 ELSE 0 END) as no_corresponde
            FROM usuarios u
            LEFT JOIN gestiones_historial g ON u.id = g.usuario_id 
                AND DATE(g.fecha_gestion) BETWEEN :desde AND :hasta
            WHERE u.rol = 'operador' AND u.activo = 1 AND u.id != 42
            GROUP BY u.id, u.nombre
            HAVING clientes_gestionados > 0
            ORDER BY tasa_al_dia_pct DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':desde' => $fecha_desde, ':hasta' => $fecha_hasta]);
        $datos = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $datos, 'titulo' => 'Efectividad (Clientes Al Día)']);
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 6. MATRIZ DE CRUCE — Operador × Estado (Tabla de doble entrada)
    // ══════════════════════════════════════════════════════════════════════════
    if ($action === 'matriz_cruce') {
        // Primero obtenemos todos los operadores
        $stmt = $pdo->prepare("
            SELECT u.id, u.nombre
            FROM usuarios u
            WHERE u.rol = 'operador' AND u.activo = 1 AND u.id != 42
            ORDER BY u.nombre
        ");
        $stmt->execute();
        $operadores = $stmt->fetchAll();

        // Estados posibles
        $estados = ['promesa', 'al_dia', 'no_responde', 'no_corresponde', 'llamar', 'numero_baja', 'carta', 'otro'];

        // Armamos matriz
        $matriz = [];
        foreach ($operadores as $op) {
            $fila = ['operador' => $op['nombre'], 'operador_id' => $op['id']];
            
            foreach ($estados as $estado) {
                $stmt = $pdo->prepare("
                    SELECT COUNT(DISTINCT g.legajo) as cantidad
                    FROM gestiones_historial g
                    WHERE g.usuario_id = ? AND g.estado = ? 
                        AND DATE(g.fecha_gestion) BETWEEN ? AND ?
                ");
                $stmt->execute([$op['id'], $estado, $fecha_desde, $fecha_hasta]);
                $res = $stmt->fetch();
                $fila[$estado] = (int)($res['cantidad'] ?? 0);
            }
            
            $fila['total'] = array_sum(array_filter(
                $fila,
                function($k) use ($estados) { return in_array($k, $estados); },
                ARRAY_FILTER_USE_KEY
            ));
            
            $matriz[] = $fila;
        }

        echo json_encode(['success' => true, 'data' => $matriz, 'estados' => $estados, 'titulo' => 'Matriz Operador × Estado']);
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 7. TOP CLIENTES GESTIONADOS — Quién gestiona los casos más complicados
    // ══════════════════════════════════════════════════════════════════════════
    if ($action === 'clientes_mas_gestionados') {
        $sql = "
            SELECT 
                c.legajo,
                c.razon_social,
                c.total_vencido,
                c.dias_atraso,
                COUNT(g.id) as total_gestiones,
                COUNT(DISTINCT g.usuario_id) as operadores_distintos,
                MAX(g.fecha_gestion) as ultima_gestion,
                (SELECT g2.estado FROM gestiones_historial g2 WHERE g2.legajo = c.legajo ORDER BY g2.id DESC LIMIT 1) as estado_actual
            FROM clientes c
            LEFT JOIN gestiones_historial g ON c.legajo = g.legajo 
                AND DATE(g.fecha_gestion) BETWEEN :desde AND :hasta
            WHERE g.id IS NOT NULL
            GROUP BY c.legajo, c.razon_social, c.total_vencido, c.dias_atraso
            ORDER BY total_gestiones DESC
            LIMIT 50
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':desde' => $fecha_desde, ':hasta' => $fecha_hasta]);
        $datos = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $datos, 'titulo' => 'Clientes Más Gestionados']);
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 8. RESUMEN GENERAL — Para la tarjeta principal del dashboard
    // ══════════════════════════════════════════════════════════════════════════
    if ($action === 'resumen_general') {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT u.id) as operadores_totales,
                COUNT(DISTINCT CASE WHEN DATE(g.fecha_gestion) BETWEEN ? AND ? THEN u.id END) as operadores_activos,
                COUNT(g.id) as total_gestiones,
                COUNT(DISTINCT g.legajo) as clientes_unicos,
                COUNT(DISTINCT g.usuario_id) as operadores_con_gestiones,
                SUM(CASE WHEN g.estado = 'promesa' THEN 1 ELSE 0 END) as promesas_totales,
                SUM(CASE WHEN g.estado = 'al_dia' THEN 1 ELSE 0 END) as al_dia_totales,
                AVG(CASE WHEN g.usuario_id IS NOT NULL THEN 1 ELSE 0 END) as promedio_gestiones_por_operador
            FROM usuarios u
            LEFT JOIN gestiones_historial g ON u.id = g.usuario_id
            WHERE u.rol = 'operador' AND u.activo = 1 AND u.id != 42
                AND (g.id IS NULL OR DATE(g.fecha_gestion) BETWEEN ? AND ?)
        ");
        $stmt->execute([$fecha_desde, $fecha_hasta, $fecha_desde, $fecha_hasta]);
        $resumen = $stmt->fetch();
        
        echo json_encode(['success' => true, 'data' => $resumen, 'titulo' => 'Resumen General']);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Acción no reconocida']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>