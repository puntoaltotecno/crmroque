<?php
/**
 * ARCHIVO: api_importar_csv.php
 */
require_once 'db.php';
ini_set('auto_detect_line_endings', TRUE);

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

function fmt_fecha($raw) {
    $raw = trim($raw);
    if (empty($raw) || strtolower($raw) === 'null') return null;
    
    // Extraer solo la parte de la fecha si viene con hora (ej: "2022-08-30 00:00:00")
    $partes = explode(' ', $raw);
    $solo_fecha = $partes[0];

    $d = DateTime::createFromFormat('d/m/Y', $solo_fecha);
    if ($d !== false) return $d->format('Y-m-d');
    $d2 = DateTime::createFromFormat('Y-m-d', $solo_fecha);
    if ($d2 !== false) return $d2->format('Y-m-d');
    return null;
}

function fmt_monto($raw) {
    $raw = preg_replace('/[^0-9.,\-]/', '', $raw);
    $raw = str_replace(',', '.', $raw);
    return (float)$raw;
}

function limpiar($str) {
    return trim(preg_replace('/\s+/', ' ', $str ?? ''));
}

function crm_str($str) {
    return mb_strtoupper(limpiar($str), 'UTF-8');
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Error al recibir archivo.']);
    exit;
}

$tmp_name = $_FILES['file']['tmp_name'];

try {
    $pdo->beginTransaction();

    // Consultas preparadas
    $stmt_check = $pdo->prepare("SELECT legajo, (SELECT estado FROM gestiones_historial g WHERE g.legajo = clientes.legajo ORDER BY id DESC LIMIT 1) as estado_actual FROM clientes WHERE legajo = ?");
    
    $stmt_insert = $pdo->prepare(
        "INSERT INTO clientes (
            l_entidad_id, legajo, razon_social, nro_documento, ultimo_pago,
            c_cuotas, localidad, domicilio,
            dias_atraso, total_vencido, vencimiento, sucursal, telefonos
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    // ESTE UPDATE AHORA SE ASEGURA DE GUARDAR EL ULTIMO PAGO DEL CSV
    $stmt_update = $pdo->prepare(
        "UPDATE clientes SET 
            l_entidad_id = ?, razon_social = ?, nro_documento = ?, ultimo_pago = ?,
            c_cuotas = ?, localidad = ?, domicilio = ?,
            dias_atraso = ?, total_vencido = ?, vencimiento = ?, 
            sucursal = ?, telefonos = ?
         WHERE legajo = ?"
    );

    $stmt_zero_deuda = $pdo->prepare("UPDATE clientes SET c_cuotas = 0, total_vencido = 0, dias_atraso = 0 WHERE legajo = ?");
    
    $stmt_al_dia = $pdo->prepare(
        "INSERT INTO gestiones_historial (legajo, usuario_id, estado, observaciones, fecha_gestion)
         VALUES (?, ?, 'al_dia', 'Gestión automática: Deuda cancelada/regularizada según importación.', NOW())"
    );

    $stmt_reingreso = $pdo->prepare(
        "INSERT INTO gestiones_historial (legajo, usuario_id, estado, observaciones, fecha_gestion)
         VALUES (?, ?, NULL, 'Gestión automática: Reingresó al reporte de mora (Pendiente)', NOW())"
    );

    $handle = fopen($tmp_name, 'r');
    if ($handle === false) throw new Exception("No se pudo leer el archivo CSV.");

    $primera_linea = fgets($handle);
    // Limpiamos BOM invisible
    $primera_linea = preg_replace('/\x{FEFF}/u', '', $primera_linea); 
    $delimitador = (strpos($primera_linea, ';') !== false) ? ';' : ',';
    rewind($handle);
    fgetcsv($handle, 0, $delimitador); // Salta títulos

    $cnt_insert = 0;
    $cnt_update = 0;
    $cnt_al_dia = 0;
    $legajos_procesados = [];
    $errores = [];

    while (($d = fgetcsv($handle, 0, $delimitador)) !== false) {
        if (empty(array_filter($d)) || count($d) < 10) continue; 

        $l_ent_id = (int)preg_replace('/[^0-9]/', '', $d[0] ?? '0');
        $legajo   = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $d[1] ?? '')); 
        if (empty($legajo)) continue;

        $legajos_procesados[] = $legajo;

        $p_razon = crm_str($d[2] ?? '');
        $p_doc   = crm_str($d[3] ?? '');
        $p_ult   = fmt_fecha($d[4] ?? null); // <-- RECUPERAMOS EL DATO DE ULTIMO PAGO DEL CSV
        $p_cta   = (int)preg_replace('/[^0-9\-]/', '', $d[5] ?? '0');
        $p_loc   = limpiar($d[6] ?? '');
        $p_dom   = limpiar($d[7] ?? '');
        $p_mora  = (int)preg_replace('/[^0-9\-]/', '', $d[8] ?? '0');
        $p_tot   = fmt_monto($d[9] ?? '0');
        $p_ven   = fmt_fecha($d[10] ?? null);
        $p_suc   = limpiar($d[11] ?? '');
        $p_tel   = limpiar($d[12] ?? '');

        // REGLA: Si la deuda viene en 0 o menos, el cliente está Al Día según el CSV
        $is_al_dia_csv = ($p_tot <= 0);

        try {
            $stmt_check->execute([$legajo]);
            $cliente_existente = $stmt_check->fetch();

            if ($cliente_existente) {
                // Existe -> Actualizar (Esto inyecta el ultimo_pago del CSV a la Base de Datos)
                $stmt_update->execute([
                    $l_ent_id, $p_razon, $p_doc, $p_ult, $p_cta, $p_loc, $p_dom,
                    $p_mora, $p_tot, $p_ven, $p_suc, $p_tel, $legajo
                ]);
                $cnt_update++;

                if ($is_al_dia_csv && $cliente_existente['estado_actual'] !== 'al_dia') {
                    // Si el CSV dice que no debe, y no estaba Al Día, lo pasamos a Al Día
                    $stmt_al_dia->execute([$legajo, $_SESSION['user_id']]);
                    $cnt_al_dia++;
                } elseif (!$is_al_dia_csv && $cliente_existente['estado_actual'] === 'al_dia') {
                    // Si volvió a tener deuda, reingresa a mora
                    $stmt_reingreso->execute([$legajo, $_SESSION['user_id']]);
                }
            } else {
                // No existe -> Insertar
                $stmt_insert->execute([
                    $l_ent_id, $legajo, $p_razon, $p_doc, $p_ult, $p_cta, $p_loc, $p_dom,
                    $p_mora, $p_tot, $p_ven, $p_suc, $p_tel
                ]);
                $cnt_insert++;

                if ($is_al_dia_csv) {
                    $stmt_al_dia->execute([$legajo, $_SESSION['user_id']]);
                    $cnt_al_dia++;
                }
            }
        } catch (Exception $ex) {
            $errores[] = "Error en legajo $legajo: " . $ex->getMessage();
        }
    }

    // ── Limpiar los ausentes (Los que desaparecieron del CSV) ──
    $todos_los_legajos = $pdo->query("SELECT legajo, (SELECT estado FROM gestiones_historial g WHERE g.legajo = clientes.legajo ORDER BY id DESC LIMIT 1) as estado_actual FROM clientes")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    foreach ($todos_los_legajos as $leg_bd => $estado_actual) {
        if (!in_array($leg_bd, $legajos_procesados)) {
            $stmt_zero_deuda->execute([$leg_bd]);
            
            if ($estado_actual !== 'al_dia') {
                $stmt_al_dia->execute([$leg_bd, $_SESSION['user_id']]);
                $cnt_al_dia++;
            }
        }
    }

    $pdo->commit();

    $msg = "Nuevos: $cnt_insert | Actualizados: $cnt_update | Pasados a Al Día ($0): $cnt_al_dia";
    if (count($errores) > 0) $msg .= "\nErrores detectados: " . count($errores);

    echo json_encode(['success' => true, 'count' => $msg, 'errores' => array_slice($errores, 0, 10)]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>