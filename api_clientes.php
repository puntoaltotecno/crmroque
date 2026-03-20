<?php
/**
 * ARCHIVO: api_clientes.php
 * ARQUITECTURA: estado, fecha_promesa y monto_promesa se leen desde la ÚLTIMA gestión
 * del historial (gestiones_historial), no desde la tabla clientes.
 */
require_once 'db.php';

if (!isset($_SESSION['user_id'])) exit;

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

try {
    $action   = $_GET['action'] ?? 'search';
    $is_admin = ($_SESSION['user_rol'] === 'admin');
    $uid      = $_SESSION['user_id'];

    // --------------------------------------------------------
    // ACCIÓN: ASIGNAR CLIENTE A OPERADOR (Solo Admin)
    // --------------------------------------------------------
    if ($action === 'assign') {
        if (!$is_admin) { echo json_encode(['success' => false, 'message' => 'No autorizado']); exit; }
        
        $legajo = trim($_POST['legajo'] ?? '');
        $op_id  = trim($_POST['usuario_id'] ?? '');

        if (empty($legajo)) {
            echo json_encode(['success' => false, 'message' => 'El cliente no tiene legajo.']); exit;
        }

        try {
            if ($op_id === '') {
                $stmt = $pdo->prepare("DELETE FROM asignaciones WHERE legajo = ?");
                $stmt->execute([$legajo]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO asignaciones (legajo, usuario_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE usuario_id = VALUES(usuario_id)");
                $stmt->execute([$legajo, $op_id]);
            }
            echo json_encode(['success' => true]);
        } catch (Exception $ex) {
            echo json_encode(['success' => false, 'message' => 'BD Error: ' . $ex->getMessage()]);
        }
        exit;
    }

    // --------------------------------------------------------
    // ACCIÓN: ESTADÍSTICAS SUPERIORES
    // --------------------------------------------------------
    if ($action === 'stats') {
        try {
            // Las promesas y mora ahora se cuentan desde el historial
            if ($is_admin) {
                $deuda = $pdo->query("SELECT SUM(total_vencido + mora) as total FROM clientes")->fetch()['total'] ?? 0;
                
                // Promesas: clientes cuya ÚLTIMA gestión es 'promesa'
                $promesas = $pdo->query(
                    "SELECT COUNT(DISTINCT g.legajo) as total 
                     FROM gestiones_historial g
                     INNER JOIN (
                         SELECT legajo, MAX(fecha_gestion) as ultima 
                         FROM gestiones_historial GROUP BY legajo
                     ) ult ON g.legajo = ult.legajo AND g.fecha_gestion = ult.ultima
                     WHERE g.estado = 'promesa'"
                )->fetch()['total'] ?? 0;

                $mora_stats = $pdo->query("SELECT COUNT(*) as total FROM clientes WHERE dias_atraso > 0")->fetch()['total'] ?? 0;
            } else {
                $deuda = $pdo->prepare("SELECT SUM(c.total_vencido + c.mora) as total FROM clientes c INNER JOIN asignaciones a ON c.legajo = a.legajo WHERE a.usuario_id = ?");
                $deuda->execute([$uid]);
                $deuda = $deuda->fetch()['total'] ?? 0;

                $promesas = $pdo->prepare(
                    "SELECT COUNT(DISTINCT g.legajo) as total 
                     FROM gestiones_historial g
                     INNER JOIN asignaciones a ON g.legajo = a.legajo
                     INNER JOIN (
                         SELECT legajo, MAX(fecha_gestion) as ultima 
                         FROM gestiones_historial GROUP BY legajo
                     ) ult ON g.legajo = ult.legajo AND g.fecha_gestion = ult.ultima
                     WHERE g.estado = 'promesa' AND a.usuario_id = ?"
                );
                $promesas->execute([$uid]);
                $promesas = $promesas->fetch()['total'] ?? 0;

                $mora_stats = $pdo->prepare("SELECT COUNT(*) as total FROM clientes c INNER JOIN asignaciones a ON c.legajo = a.legajo WHERE c.dias_atraso > 0 AND a.usuario_id = ?");
                $mora_stats->execute([$uid]);
                $mora_stats = $mora_stats->fetch()['total'] ?? 0;
            }
            echo json_encode(['deuda' => $deuda, 'promesas' => $promesas, 'mora' => $mora_stats]);
        } catch (Exception $e) {
            echo json_encode(['deuda' => 0, 'promesas' => 0, 'mora' => 0]);
        }
        exit;
    }

    // --------------------------------------------------------
    // ACCIÓN: BUSCAR Y LISTAR CLIENTES
    // --------------------------------------------------------
    $q          = $_GET['q']           ?? '';
    $f_estado   = trim($_GET['estado']       ?? '');
    $f_operador = (int)($_GET['operador_id'] ?? 0);

    // Subquery que trae la ÚLTIMA gestión de cada legajo
    $subquery_ultima = "
        SELECT g.*
        FROM gestiones_historial g
        INNER JOIN (
            SELECT legajo, MAX(fecha_gestion) AS ultima
            FROM gestiones_historial
            GROUP BY legajo
        ) ult ON g.legajo = ult.legajo AND g.fecha_gestion = ult.ultima
    ";

    // Núcleo SELECT — estado, fecha_promesa y monto_promesa vienen del historial
    $select_core = "SELECT c.*, 
        u.nombre AS operador_asignado,
        u.id     AS operador_id,
        IFNULL(gest.estado, 'sin_gestion')           AS estado,
        IFNULL(gest.fecha_promesa, NULL)              AS fecha_promesa,
        IFNULL(gest.monto_promesa, 0)                 AS monto_promesa,
        CASE 
            WHEN c.dias_atraso > 90                  THEN 'rojo'
            WHEN gest.estado = 'promesa'             THEN 'amarillo'
            WHEN gest.estado = 'llamar'              THEN 'verde'
            ELSE 'verde'
        END AS semaforo";

    // Filtros dinámicos
    $where_extra = '';
    $params = ['q' => "%$q%"];

    if (!empty($f_estado)) {
        $where_extra .= " AND gest.estado = :estado";
        $params[':estado'] = $f_estado;
    }
    if ($f_operador > 0) {
        $where_extra .= " AND a.usuario_id = :f_op";
        $params[':f_op'] = $f_operador;
    }

    if ($is_admin) {
        $sql = "$select_core
            FROM clientes c
            LEFT JOIN asignaciones a   ON c.legajo = a.legajo
            LEFT JOIN usuarios u       ON a.usuario_id = u.id
            LEFT JOIN ($subquery_ultima) gest ON c.legajo = gest.legajo
            WHERE (c.razon_social LIKE :q OR c.nro_documento LIKE :q OR c.legajo LIKE :q OR c.sucursal LIKE :q)
            $where_extra
            ORDER BY c.razon_social ASC LIMIT 200";
    } else {
        $params[':uid'] = $uid;
        $sql = "$select_core
            FROM clientes c
            INNER JOIN asignaciones a  ON c.legajo = a.legajo
            INNER JOIN usuarios u      ON a.usuario_id = u.id
            LEFT JOIN ($subquery_ultima) gest ON c.legajo = gest.legajo
            WHERE a.usuario_id = :uid
            AND (c.razon_social LIKE :q OR c.nro_documento LIKE :q OR c.legajo LIKE :q OR c.sucursal LIKE :q)
            $where_extra
            ORDER BY c.razon_social ASC LIMIT 200";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll();
    echo json_encode($resultados, JSON_INVALID_UTF8_SUBSTITUTE);

} catch (PDOException $e) {
    echo json_encode([['id' => 0, 'legajo' => 'ERR', 'razon_social' => 'Error SQL: ' . $e->getMessage()]]);
}
?>