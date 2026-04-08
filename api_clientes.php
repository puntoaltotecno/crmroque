<?php
/**
 * ARCHIVO: api_clientes.php
 * VERSIÓN: 1.8 - Agregado soporte para columna MOTO
 */
require_once 'db.php';

if (!isset($_SESSION['user_id'])) exit;

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

try {
    $action   = $_GET['action'] ?? 'search';
    $rol      = $_SESSION['user_rol'];
    $can_see_all = ($rol === 'admin' || $rol === 'colaborador');
    $uid      = $_SESSION['user_id'];

    $q          = $_GET['q'] ?? '';
    $f_estado   = $_GET['estado'] ?? '';
    $f_operador = isset($_GET['operador_id']) ? (int)$_GET['operador_id'] : 0;
    
    // ── NUEVO: Captura del filtro MOTO ──
    $f_moto     = isset($_GET['moto']) ? (int)$_GET['moto'] : 0;

    $subquery_ultima = "SELECT g1.* FROM gestiones_historial g1
                        JOIN (SELECT MAX(id) as max_id FROM gestiones_historial GROUP BY legajo) g2
                        ON g1.id = g2.max_id";

    // CORE SELECT (incluye c.moto en la selección)
    $select_core = "SELECT c.*, 
                    u.nombre as operador_asignado,
                    u.id as operador_id,
                    IFNULL(gest.estado, 'sin_gestion') as estado_actual,
                    gest.fecha_promesa,
                    gest.monto_promesa,
                    CASE 
                        WHEN gest.estado = 'promesa' AND gest.fecha_promesa < CURDATE() THEN 'rojo'
                        WHEN gest.estado = 'promesa' THEN 'amarillo'
                        WHEN gest.estado IS NULL OR gest.estado = 'sin_gestion' THEN 'blanco'
                        ELSE 'verde'
                    END as semaforo";

    // ── NOTIFICACIONES (Alertas de Agenda) ──
    if ($action === 'notificaciones') {
        $where_noti = " WHERE gest.fecha_promesa IS NOT NULL AND gest.fecha_promesa <= CURDATE() ";
        $params_noti = [];

        if (!$can_see_all) {
            $where_noti .= " AND a.usuario_id = :uid ";
            $params_noti[':uid'] = $uid;
        }

        $sql_noti = "$select_core
            FROM clientes c
            LEFT JOIN asignaciones a   ON c.legajo = a.legajo
            LEFT JOIN usuarios u       ON a.usuario_id = u.id
            LEFT JOIN ($subquery_ultima) gest ON c.legajo = gest.legajo
            $where_noti
            ORDER BY gest.fecha_promesa ASC
            LIMIT 50";

        $stmt = $pdo->prepare($sql_noti);
        $stmt->execute($params_noti);
        $notificaciones = $stmt->fetchAll();

        echo json_encode([
            'count' => count($notificaciones),
            'data' => $notificaciones
        ]);
        exit;
    }

    // ── CONSTRUIR WHERE DINÁMICO ──
    $where = " WHERE (c.razon_social LIKE :q OR c.nro_documento LIKE :q OR c.legajo LIKE :q OR c.sucursal LIKE :q)";
    $params = [':q' => "%$q%"];

    if (!empty($f_estado)) {
        if ($f_estado === 'sin_gestion') $where .= " AND gest.estado IS NULL";
        else {
            $where .= " AND gest.estado = :f_estado";
            $params[':f_estado'] = $f_estado;
        }
    }

    // ── NUEVO: Filtro MOTO ──
    if ($f_moto === 1) {
        $where .= " AND c.moto = 1";
    }

    // ── REGLA DE BÚSQUEDA UNIVERSAL ──
    if ($can_see_all) {
        if ($f_operador > 0) {
            $where .= " AND a.usuario_id = :f_op";
            $params[':f_op'] = $f_operador;
        } elseif ($f_operador === -1) {
            $where .= " AND a.usuario_id IS NULL";
        }
    } else {
        if (empty($q)) {
            $where .= " AND a.usuario_id = :uid";
            $params[':uid'] = $uid;
        }
    }

    // ── ESTADÍSTICAS ──
    if ($action === 'stats') {
        $sql_global = "SELECT SUM(total_vencido) as deuda_total, COUNT(id) as total_clientes FROM clientes";
        $res_global = $pdo->query($sql_global)->fetch();

        $sql_filtered = "SELECT 
            SUM(CASE WHEN gest.estado = 'promesa' THEN 1 ELSE 0 END) as promesas,
            COUNT(c.id) as clientes_filtrados
            FROM clientes c
            LEFT JOIN asignaciones a ON c.legajo = a.legajo
            LEFT JOIN ($subquery_ultima) gest ON c.legajo = gest.legajo
            $where";

        $stmt = $pdo->prepare($sql_filtered);
        $stmt->execute($params);
        $res = $stmt->fetch();

        echo json_encode([
            'deuda_total' => ($rol === 'operador') ? 0 : ($res_global['deuda_total'] ?: 0),
            'total_clientes' => ($rol === 'operador') ? 0 : ($res_global['total_clientes'] ?: 0),
            'promesas' => $res['promesas'] ?: 0,
            'clientes_filtrados' => $res['clientes_filtrados'] ?: 0
        ]);
        exit;
    }

    // ── LISTADO PRINCIPAL ──
    $limit = min((int)($_GET['limit'] ?? 200), 500);

    $sql = "$select_core
            FROM clientes c
            LEFT JOIN asignaciones a   ON c.legajo = a.legajo
            LEFT JOIN usuarios u       ON a.usuario_id = u.id
            LEFT JOIN ($subquery_ultima) gest ON c.legajo = gest.legajo
            $where
            ORDER BY CAST(c.dias_atraso AS SIGNED) ASC, c.razon_social ASC 
            LIMIT $limit";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll());
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}