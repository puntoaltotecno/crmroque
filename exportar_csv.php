<?php
/**
 * ARCHIVO: exportar_csv.php
 * Exporta la cartera actual en formato CSV nativo con soporte UTF-8 para Excel.
 */
require_once 'db.php';

// Validar sesión y rol (Solo Admin y Colaborador pueden exportar la base completa)
if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] !== 'admin' && $_SESSION['user_rol'] !== 'colaborador')) {
    exit("No tienes permisos para realizar esta acción.");
}

// Recibir filtros desde el frontend
$q = trim($_GET['q'] ?? '');
$estado = trim($_GET['estado'] ?? '');
$operador_id = trim($_GET['operador_id'] ?? '');

// Preparar los Headers para forzar la descarga del CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="Cartera_CRM_Roque_' . date('Ymd_His') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Abrir la salida de datos directa
$output = fopen('php://output', 'w');

// IMPORTANTE: Escribir el BOM (Byte Order Mark) de UTF-8 para que Excel reconozca tildes y eñes
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// 1. Escribir las cabeceras del CSV
fputcsv($output, [
    'Legajo', 
    'Razón Social', 
    'Documento', 
    'Sucursal', 
    'Deuda Vencida', 
    'Días Atraso', 
    'Cuotas',
    'Último Pago',
    'Estado Actual', 
    'Fecha Últ. Gestión',
    'Operador Asignado'
]);

// 2. Construir la consulta SQL dinámica con el estado actual del historial
$sql = "SELECT 
            c.legajo, 
            c.razon_social, 
            c.nro_documento, 
            c.sucursal,
            c.total_vencido,
            c.dias_atraso,
            c.c_cuotas,
            c.ultimo_pago,
            COALESCE(ult_gest.estado, 'sin_gestion') AS estado_actual,
            ult_gest.fecha_gestion,
            u_op.nombre AS operador_asignado
        FROM clientes c
        LEFT JOIN (
            SELECT g.legajo, g.estado, g.fecha_gestion
            FROM gestiones_historial g
            INNER JOIN (
                SELECT legajo, MAX(id) AS max_id
                FROM gestiones_historial
                GROUP BY legajo
            ) mg ON g.id = mg.max_id
        ) ult_gest ON c.legajo = ult_gest.legajo
        LEFT JOIN asignaciones a ON c.legajo = a.legajo
        LEFT JOIN usuarios u_op ON a.usuario_id = u_op.id
        WHERE 1=1";

$params = [];

// Aplicar filtro de búsqueda de texto
if ($q !== '') {
    $phoneSearch = preg_replace('/[^0-9]/', '', $q);
    $phoneCondition = "";
    if (!empty($phoneSearch)) {
        $phoneCondition = " OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(c.telefonos, ' ', ''), '-', ''), '+', ''), '(', ''), ')', '') LIKE ?";
    }
    
    $sql .= " AND (c.legajo LIKE ? OR c.razon_social LIKE ? OR c.nro_documento LIKE ?" . $phoneCondition . ")";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
    if (!empty($phoneSearch)) {
        $params[] = "%$phoneSearch%";
    }
}

// Aplicar filtro de estado
if ($estado !== '') {
    if ($estado === 'sin_gestion') {
        $sql .= " AND ult_gest.estado IS NULL";
    } else {
        $sql .= " AND ult_gest.estado = ?";
        $params[] = $estado;
    }
}

// Aplicar filtro de operador
if ($operador_id !== '' && $operador_id !== '0') {
    if ($operador_id === '-1') {
        $sql .= " AND a.usuario_id IS NULL";
    } else {
        $sql .= " AND a.usuario_id = ?";
        $params[] = $operador_id;
    }
}

// Ordenar por los de mayor deuda
$sql .= " ORDER BY c.total_vencido DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// 3. Escribir los datos fila por fila
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Formatear estado para que se vea legible (Ej: "NO RESPONDE" en vez de "no_responde")
    $estado_legible = strtoupper(str_replace('_', ' ', $row['estado_actual']));
    
    // Si no tiene fecha de gestión, colocar guión
    $fecha_g = $row['fecha_gestion'] ? date('d/m/Y H:i', strtotime($row['fecha_gestion'])) : '-';

    fputcsv($output, [
        $row['legajo'],
        $row['razon_social'],
        $row['nro_documento'],
        $row['sucursal'] ?? 'Central',
        $row['total_vencido'],
        $row['dias_atraso'],
        $row['c_cuotas'],
        $row['ultimo_pago'],
        $estado_legible,
        $fecha_g,
        $row['operador_asignado'] ?? 'SIN ASIGNAR'
    ]);
}

fclose($output);
exit;
?>