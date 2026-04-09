<?php
/**
 * ARCHIVO: api_mi_rendimiento.php
 * Panel personal del operador: rendimiento, ranking público y agenda del día
 */
require_once 'db.php';
ob_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Sin sesión']);
    exit;
}

ob_clean();

$uid = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? 'rendimiento';

try {

    // ══════════════════════════════════════════════════════════════════
    // 1. MI RENDIMIENTO — Estadísticas personales del operador logueado
    // ══════════════════════════════════════════════════════════════════
    if ($action === 'rendimiento') {

        // Total de clientes asignados a mí
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM asignaciones WHERE usuario_id = ?");
        $stmt->execute([$uid]);
        $total_asignados = (int)$stmt->fetchColumn();

        // Clientes distintos que gestioné alguna vez
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT legajo) FROM gestiones_historial WHERE usuario_id = ?");
        $stmt->execute([$uid]);
        $total_gestionados = (int)$stmt->fetchColumn();

        // Gestiones realizadas hoy
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM gestiones_historial WHERE usuario_id = ? AND DATE(fecha_gestion) = CURDATE()");
        $stmt->execute([$uid]);
        $gestiones_hoy = (int)$stmt->fetchColumn();

        // Gestiones esta semana (últimos 7 días)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM gestiones_historial WHERE usuario_id = ? AND DATE(fecha_gestion) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        $stmt->execute([$uid]);
        $gestiones_semana = (int)$stmt->fetchColumn();

        // Clientes AL DÍA en mi cartera (sin importar quién hizo la gestión)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM asignaciones asg
            JOIN gestiones_historial gh ON asg.legajo = gh.legajo
            WHERE asg.usuario_id = ?
              AND gh.id = (SELECT MAX(id) FROM gestiones_historial WHERE legajo = asg.legajo)
              AND gh.estado = 'al_dia'
        ");
        $stmt->execute([$uid]);
        $al_dia = (int)$stmt->fetchColumn();

        // Promesas activas (vigentes, no vencidas)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM asignaciones asg
            JOIN gestiones_historial gh ON asg.legajo = gh.legajo
            WHERE asg.usuario_id = ?
              AND gh.id = (SELECT MAX(id) FROM gestiones_historial WHERE legajo = asg.legajo)
              AND gh.estado = 'promesa'
              AND (gh.fecha_promesa IS NULL OR gh.fecha_promesa >= CURDATE())
        ");
        $stmt->execute([$uid]);
        $promesas_activas = (int)$stmt->fetchColumn();

        // Promesas vencidas (urgente gestionar)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM asignaciones asg
            JOIN gestiones_historial gh ON asg.legajo = gh.legajo
            WHERE asg.usuario_id = ?
              AND gh.id = (SELECT MAX(id) FROM gestiones_historial WHERE legajo = asg.legajo)
              AND gh.estado = 'promesa'
              AND gh.fecha_promesa < CURDATE()
        ");
        $stmt->execute([$uid]);
        $promesas_vencidas = (int)$stmt->fetchColumn();

        // Ranking del mes (últimos 30 días) para saber mi posición
        $stmt = $pdo->prepare("
            SELECT u.id, u.nombre, COUNT(g.id) as gestiones
            FROM usuarios u
            LEFT JOIN gestiones_historial g ON u.id = g.usuario_id
                AND DATE(g.fecha_gestion) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            WHERE u.rol = 'operador' AND u.activo = 1 AND u.id != 42
            GROUP BY u.id, u.nombre
            ORDER BY gestiones DESC
        ");
        $stmt->execute();
        $ranking = $stmt->fetchAll();

        $mi_posicion      = 1;
        $mi_gestiones_mes = 0;
        $max_gestiones    = 0;
        $nombre_lider     = '';

        foreach ($ranking as $i => $r) {
            if ($i === 0) {
                $max_gestiones = (int)$r['gestiones'];
                $nombre_lider  = $r['nombre'];
            }
            if ((int)$r['id'] === $uid) {
                $mi_posicion      = $i + 1;
                $mi_gestiones_mes = (int)$r['gestiones'];
            }
        }

        echo json_encode([
            'success'            => true,
            'total_asignados'    => $total_asignados,
            'total_gestionados'  => $total_gestionados,
            'gestiones_hoy'      => $gestiones_hoy,
            'gestiones_semana'   => $gestiones_semana,
            'al_dia'             => $al_dia,
            'promesas_activas'   => $promesas_activas,
            'promesas_vencidas'  => $promesas_vencidas,
            'mi_posicion'        => $mi_posicion,
            'mi_gestiones_mes'   => $mi_gestiones_mes,
            'max_gestiones_mes'  => $max_gestiones,
            'nombre_lider'       => $nombre_lider,
            'total_operadores'   => count($ranking),
            'diferencia_lider'   => max(0, $max_gestiones - $mi_gestiones_mes),
        ]);
        exit;
    }

    // ══════════════════════════════════════════════════════════════════
    // 2. RANKING PÚBLICO — Visible para todos, sin datos sensibles
    // ══════════════════════════════════════════════════════════════════
    if ($action === 'ranking_publico') {
        $stmt = $pdo->prepare("
            SELECT
                u.id,
                u.nombre,
                COUNT(g.id) as gestiones,
                SUM(CASE WHEN g.estado = 'promesa' THEN 1 ELSE 0 END) as promesas,
                (SELECT COUNT(*)
                 FROM asignaciones asg
                 JOIN gestiones_historial gh ON asg.legajo = gh.legajo
                 WHERE asg.usuario_id = u.id
                   AND gh.id = (SELECT MAX(id) FROM gestiones_historial WHERE legajo = asg.legajo)
                   AND gh.estado = 'al_dia'
                ) as al_dia
            FROM usuarios u
            LEFT JOIN gestiones_historial g ON u.id = g.usuario_id
                AND DATE(g.fecha_gestion) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            WHERE u.rol = 'operador' AND u.activo = 1 AND u.id != 42
            GROUP BY u.id, u.nombre
            ORDER BY gestiones DESC
        ");
        $stmt->execute();
        $datos = $stmt->fetchAll();

        foreach ($datos as &$d) {
            $d['es_yo'] = ((int)$d['id'] === $uid);
        }

        echo json_encode(['success' => true, 'data' => $datos]);
        exit;
    }

    // ══════════════════════════════════════════════════════════════════
    // 3. MI AGENDA DEL DÍA — Clientes con promesa vencida o a llamar hoy
    // ══════════════════════════════════════════════════════════════════
    if ($action === 'agenda') {
        // Consultar el rol del usuario para la agenda
        $stmt_rol = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
        $stmt_rol->execute([$uid]);
        $rol = $stmt_rol->fetchColumn();
        $can_see_all = in_array($rol, ['admin', 'colaborador']);

        $where = "";
        $params = [];
        if (!$can_see_all) {
            $where = " AND asg.usuario_id = ?";
            $params[] = $uid;
        }

        $stmt = $pdo->prepare("
            SELECT
                c.legajo,
                c.razon_social,
                c.telefonos,
                c.domicilio,
                c.total_vencido,
                c.dias_atraso,
                gh.estado,
                gh.fecha_promesa,
                gh.monto_promesa,
                gh.observaciones,
                CASE
                    WHEN gh.estado = 'promesa' AND gh.fecha_promesa < CURDATE() THEN 'vencida'
                    WHEN gh.estado = 'promesa' AND gh.fecha_promesa = CURDATE() THEN 'hoy'
                    WHEN gh.estado = 'llamar'  AND (gh.fecha_promesa IS NULL OR gh.fecha_promesa <= CURDATE()) THEN 'llamar'
                    ELSE 'pendiente'
                END as tipo_agenda
            FROM asignaciones asg
            JOIN clientes c ON asg.legajo = c.legajo
            LEFT JOIN gestiones_historial gh ON asg.legajo = gh.legajo
                AND gh.id = (SELECT MAX(id) FROM gestiones_historial WHERE legajo = asg.legajo)
            WHERE (
                  (gh.estado = 'promesa' AND gh.fecha_promesa <= CURDATE())
                  OR (gh.estado = 'llamar' AND (gh.fecha_promesa IS NULL OR gh.fecha_promesa <= CURDATE()))
              )
              $where
            ORDER BY
                CASE WHEN gh.estado = 'promesa' AND gh.fecha_promesa < CURDATE() THEN 0
                     WHEN gh.estado = 'promesa' AND gh.fecha_promesa = CURDATE() THEN 1
                     ELSE 2 END ASC,
                gh.fecha_promesa ASC,
                c.total_vencido DESC
            LIMIT 50
        ");
        $stmt->execute($params);
        $agenda = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $agenda, 'count' => count($agenda)]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Acción no reconocida']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
