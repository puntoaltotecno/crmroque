<?php
/**
 * ARCHIVO: api_importar_csv.php
 * Descripción: Importador blindado. Mapeado exacto al CSV exportado por el sistema.
 *
 * COLUMNAS DEL CSV (índice 0):
 *  0  l_entidad_id
 *  1  legajo
 *  2  razon_social
 *  3  nro_documento
 *  4  ultimo_pago
 *  5  c_cuotas
 *  6  localidad       ← se combina con domicilio
 *  7  domicilio
 *  8  dias_atraso
 *  9  total_vencido
 *  10 vencimiento
 *  11 sucursal
 *  12 telefonos
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
    // Tomamos solo la parte de fecha, ignoramos la hora
    $solo = explode(' ', $raw)[0];
    // Formato Y-m-d → ya está bien
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $solo)) return $solo;
    // Formato d/m/Y o d-m-Y
    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $solo, $m))
        return $m[3].'-'.sprintf('%02d',$m[2]).'-'.sprintf('%02d',$m[1]);
    return null;
}

function fmt_monto($raw) {
    $v = trim(str_replace(['$',' '], '', $raw));
    // Detectar si tiene separador de miles con punto y decimal con coma
    if (strpos($v,'.') !== false && strpos($v,',') !== false) {
        $v = str_replace('.', '', $v);
        $v = str_replace(',', '.', $v);
    } elseif (strpos($v,',') !== false) {
        $v = str_replace(',', '.', $v);
    }
    return (float)$v;
}

function limpiar($t) {
    $t = trim($t);
    if (empty($t)) return '';
    return mb_convert_encoding($t, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
}
// ─────────────────────────────────────────────────────────────────────────

if (!isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'No se recibió el archivo.']);
    exit;
}

$handle = fopen($_FILES['file']['tmp_name'], 'r');

// Detectar delimitador
$primera = fgets($handle);
$delim   = (strpos($primera, ';') !== false) ? ';' : ',';
rewind($handle);
fgetcsv($handle, 0, $delim); // Saltar encabezado

$sql = "INSERT INTO clientes (
            l_entidad_id, legajo, razon_social, nro_documento,
            ultimo_pago, c_cuotas, domicilio,
            dias_atraso, total_vencido, vencimiento, sucursal, telefonos
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            legajo        = VALUES(legajo),
            razon_social  = VALUES(razon_social),
            nro_documento = VALUES(nro_documento),
            ultimo_pago   = VALUES(ultimo_pago),
            c_cuotas      = VALUES(c_cuotas),
            domicilio     = VALUES(domicilio),
            dias_atraso   = VALUES(dias_atraso),
            total_vencido = VALUES(total_vencido),
            vencimiento   = VALUES(vencimiento),
            sucursal      = VALUES(sucursal),
            telefonos     = VALUES(telefonos)";

$stmt    = $pdo->prepare($sql);
$count   = 0;
$errores = [];
$fila    = 1;

$pdo->beginTransaction();

try {
    while (($d = fgetcsv($handle, 0, $delim)) !== FALSE) {
        $fila++;
        if (empty(array_filter($d))) continue;
        $d = array_pad($d, 13, '');

        $entidad_id = (int)preg_replace('/[^0-9]/', '', $d[0]);
        if ($entidad_id === 0) continue;

        // Columna 6 = localidad, columna 7 = domicilio
        // Las combinamos: "DOMICILIO - LOCALIDAD"
        $domicilio = limpiar($d[7]);
        $localidad = limpiar($d[6]);
        if (!empty($localidad) && !empty($domicilio)) {
            $domicilio_completo = $domicilio . ' - ' . $localidad;
        } elseif (!empty($localidad)) {
            $domicilio_completo = $localidad;
        } else {
            $domicilio_completo = $domicilio;
        }

        try {
            $stmt->execute([
                $entidad_id,                                           // l_entidad_id
                limpiar($d[1]),                                        // legajo
                limpiar($d[2]),                                        // razon_social
                limpiar($d[3]),                                        // nro_documento
                fmt_fecha($d[4]),                                      // ultimo_pago
                (int)preg_replace('/[^0-9\-]/', '', $d[5] ?? '0'),    // c_cuotas
                $domicilio_completo,                                   // domicilio
                (int)preg_replace('/[^0-9\-]/', '', $d[8] ?? '0'),    // dias_atraso
                fmt_monto($d[9]),                                      // total_vencido
                fmt_fecha($d[10]),                                     // vencimiento
                limpiar($d[11]),                                       // sucursal
                limpiar($d[12]),                                       // telefonos
            ]);
            $count++;
        } catch (Exception $e) {
            $errores[] = "Fila {$fila} (entidad {$entidad_id}): " . $e->getMessage();
        }
    }

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'count' => 'Error crítico: ' . $e->getMessage()]);
    exit;
}

fclose($handle);

$msg = "{$count} clientes importados/actualizados con éxito.";
if (!empty($errores)) {
    $msg .= "\n\nAdvertencias:\n" . implode("\n", array_slice($errores, 0, 5));
}

echo json_encode(['success' => true, 'count' => $msg]);
?>