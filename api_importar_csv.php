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

// ── Helpers ───────────────────────────────────────────────────────────────
function fmt_fecha($raw) {
    $raw = trim($raw);
    if (empty($raw) || strtolower($raw) === 'null') return null;
    $d = DateTime::createFromFormat('d/m/Y', $raw);
    if ($d !== false) return $d->format('Y-m-d');
    $d2 = DateTime::createFromFormat('Y-m-d', $raw);
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

// ── Recepción del Archivo ─────────────────────────────────────────────────
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo o hubo un error de subida.']);
    exit;
}

$tmp_name = $_FILES['file']['tmp_name'];

try {
    $pdo->beginTransaction();

    // 1. Fotografía de la base: Obtenemos todos los legajos y su estado actual (la última gestión)
    $stmt_legajos = $pdo->query(
        "SELECT c.legajo, (SELECT estado FROM gestiones_historial g WHERE g.legajo = c.legajo ORDER BY id DESC LIMIT 1) as estado_actual 
         FROM clientes c"
    );
    $datos_bd = $stmt_legajos->fetchAll(PDO::FETCH_KEY_PAIR); // Formato: [legajo => estado_actual]
    $legajos_bd = array_keys($datos_bd);

    $stmt_insert = $pdo->prepare(
        "INSERT INTO clientes (
            l_entidad_id, legajo, razon_social, nro_documento,
            ultimo_pago, c_cuotas, localidad, domicilio,
            dias_atraso, total_vencido, vencimiento, sucursal, telefonos
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt_update = $pdo->prepare(
        "UPDATE clientes SET 
            l_entidad_id = ?, razon_social = ?, nro_documento = ?,
            ultimo_pago = ?, c_cuotas = ?, localidad = ?, domicilio = ?,
            dias_atraso = ?, total_vencido = ?, vencimiento = ?, 
            sucursal = ?, telefonos = ?
         WHERE legajo = ?"
    );

    // Gestión para los que desaparecen del CSV (Pasan a Al Día)
    $stmt_al_dia = $pdo->prepare(
        "INSERT INTO gestiones_historial (legajo, usuario_id, estado, observaciones, fecha_gestion)
         VALUES (?, ?, 'al_dia', 'Gestión automática: No figura en importación', NOW())"
    );

    // Gestión para los que REINGRESAN (Pierden el "Al Día" y vuelven a pendiente/NULL)
    $stmt_reingreso = $pdo->prepare(
        "INSERT INTO gestiones_historial (legajo, usuario_id, estado, observaciones, fecha_gestion)
         VALUES (?, ?, NULL, 'Gestión automática: Reingreso en importación CSV (Pendiente)', NOW())"
    );

    $handle = fopen($tmp_name, 'r');
    if ($handle === false) throw new Exception("No se pudo leer el archivo CSV.");

    $primera_linea = fgets($handle);
    $delimitador = (strpos($primera_linea, ';') !== false) ? ';' : ',';
    rewind($handle);

    $header = fgetcsv($handle, 0, $delimitador);
    if (!$header || count($header) < 13) throw new Exception("El CSV no tiene las 13 columnas requeridas.");

    $cnt_insert = 0;
    $cnt_update = 0;
    $cnt_al_dia = 0;
    $filas_csv = [];
    $errores = [];

    // ── Procesar filas del CSV ───────────────────────────────────────────────
    while (($d = fgetcsv($handle, 0, $delimitador)) !== false) {
        if (count($d) < 13) continue;

        $l_ent_id = (int)preg_replace('/[^0-9]/', '', $d[0]);
        $legajo   = trim($d[1]);
        if (empty($legajo)) continue;

        $filas_csv[$legajo] = true;

        $p_razon = crm_str($d[2]);
        $p_doc   = crm_str($d[3]);
        $p_ult   = fmt_fecha($d[4]);
        $p_cta   = (int)preg_replace('/[^0-9\-]/', '', $d[5] ?? '0');
        $p_loc   = limpiar($d[6]);
        $p_dom   = limpiar($d[7]);
        $p_mora  = (int)preg_replace('/[^0-9\-]/', '', $d[8] ?? '0');
        $p_tot   = fmt_monto($d[9]);
        $p_ven   = fmt_fecha($d[10]);
        $p_suc   = limpiar($d[11]);
        $p_tel   = limpiar($d[12]);

        try {
            if (in_array($legajo, $legajos_bd)) {
                // Existe -> Actualizar
                $stmt_update->execute([
                    $l_ent_id, $p_razon, $p_doc, $p_ult, $p_cta, $p_loc, $p_dom,
                    $p_mora, $p_tot, $p_ven, $p_suc, $p_tel, $legajo
                ]);
                $cnt_update++;

                // -- REGLA: Si estaba al día, resetearlo a pendiente (NULL) --
                if (array_key_exists($legajo, $datos_bd) && $datos_bd[$legajo] === 'al_dia') {
                    $stmt_reingreso->execute([$legajo, $_SESSION['user_id']]);
                }

            } else {
                // No existe -> Insertar
                $stmt_insert->execute([
                    $l_ent_id, $legajo, $p_razon, $p_doc, $p_ult, $p_cta, $p_loc, $p_dom,
                    $p_mora, $p_tot, $p_ven, $p_suc, $p_tel
                ]);
                $cnt_insert++;
            }
        } catch (Exception $ex) {
            $errores[] = "Legajo $legajo: " . $ex->getMessage();
        }
    }

    // ── Marcar como "al_dia" los que YA NO están en el CSV ──────────────────
    $legajos_ausentes = array_diff($legajos_bd, array_keys($filas_csv));

    foreach ($legajos_ausentes as $leg_ausente) {
        // Solo insertamos si su estado anterior NO era ya 'al_dia'
        if (array_key_exists($leg_ausente, $datos_bd) && $datos_bd[$leg_ausente] !== 'al_dia') {
            $stmt_al_dia->execute([$leg_ausente, $_SESSION['user_id']]);
            $cnt_al_dia++;
        } elseif (!array_key_exists($leg_ausente, $datos_bd)) {
            $stmt_al_dia->execute([$leg_ausente, $_SESSION['user_id']]);
            $cnt_al_dia++;
        }
    }

    $pdo->commit();

    $msg = "Nuevos: $cnt_insert | Actualizados: $cnt_update | Pasados a Al Día: $cnt_al_dia";
    if (count($errores) > 0) {
        $msg .= "\nErrores: " . count($errores) . " (revisar log)";
    }

    echo json_encode(['success' => true, 'count' => $msg, 'errores' => array_slice($errores, 0, 10)]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error crítico: ' . $e->getMessage()]);
}
?>