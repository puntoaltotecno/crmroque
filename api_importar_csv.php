<?php
/**
 * ARCHIVO: api_importar_csv.php
 * Descripción: Importador diario. 
 * - Actualiza todos los campos de clientes existentes (excepto legajo)
 * - Inserta clientes nuevos
 * - Los clientes que YA NO aparecen en el CSV reciben una gestión automática "al_dia"
 *
 * COLUMNAS DEL CSV (índice 0):
 *  0  l_entidad_id
 *  1  legajo
 *  2  razon_social
 *  3  nro_documento
 *  4  ultimo_pago
 *  5  c_cuotas
 *  6  localidad
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
    $solo = explode(' ', $raw)[0];
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $solo)) return $solo;
    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $solo, $m))
        return $m[3].'-'.sprintf('%02d',$m[2]).'-'.sprintf('%02d',$m[1]);
    return null;
}

function fmt_monto($raw) {
    $v = trim(str_replace(['$',' '], '', $raw));
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
$primera = fgets($handle);
$delim   = (strpos($primera, ';') !== false) ? ';' : ',';
rewind($handle);
fgetcsv($handle, 0, $delim); // Saltar encabezado

// ── 1. Leer todos los legajos que están en el CSV ─────────────────────────
$legajos_csv = [];
$filas_csv   = [];
while (($d = fgetcsv($handle, 0, $delim)) !== FALSE) {
    if (empty(array_filter($d))) continue;
    $d = array_pad($d, 13, '');
    $legajo = limpiar($d[1]);
    if (!empty($legajo)) {
        $legajos_csv[$legajo] = $d;
        $filas_csv[] = $d;
    }
}
fclose($handle);

// ── 2. Obtener legajos que ya existen en la BD ────────────────────────────
$legajos_bd = [];
$stmt_leg = $pdo->query("SELECT legajo FROM clientes WHERE legajo IS NOT NULL AND legajo != ''");
while ($r = $stmt_leg->fetch()) {
    $legajos_bd[] = $r['legajo'];
}

// ── 3. Detectar los que desaparecieron del CSV → marcar como "al_dia" ────
$legajos_ausentes = array_diff($legajos_bd, array_keys($legajos_csv));

// ── 4. Preparar INSERT/UPDATE de clientes ────────────────────────────────
$sql_upsert = "INSERT INTO clientes (
                    l_entidad_id, legajo, razon_social, nro_documento,
                    ultimo_pago, c_cuotas, domicilio,
                    dias_atraso, total_vencido, vencimiento, sucursal, telefonos
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
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
// NOTA: legajo NO está en el ON DUPLICATE UPDATE — nunca se sobreescribe

$stmt_upsert = $pdo->prepare($sql_upsert);

// Insertar gestión "al_dia" para los ausentes
$sql_al_dia = "INSERT INTO gestiones_historial 
                    (legajo, usuario_id, estado, observaciones, fecha_gestion)
               VALUES (?, ?, 'al_dia', 'Marcado automáticamente como al día por importación CSV', NOW())";
$stmt_al_dia = $pdo->prepare($sql_al_dia);

$count_upsert  = 0;
$count_al_dia  = 0;
$errores       = [];
$fila_num      = 1;

$pdo->beginTransaction();

try {
    // Procesar todas las filas del CSV
    foreach ($filas_csv as $d) {
        $fila_num++;
        $entidad_id = (int)preg_replace('/[^0-9]/', '', $d[0]);
        if ($entidad_id === 0) continue;

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
            $stmt_upsert->execute([
                $entidad_id,
                limpiar($d[1]),
                limpiar($d[2]),
                limpiar($d[3]),
                fmt_fecha($d[4]),
                (int)preg_replace('/[^0-9\-]/', '', $d[5] ?? '0'),
                $domicilio_completo,
                (int)preg_replace('/[^0-9\-]/', '', $d[8] ?? '0'),
                fmt_monto($d[9]),
                fmt_fecha($d[10]),
                limpiar($d[11]),
                limpiar($d[12]),
            ]);
            $count_upsert++;
        } catch (Exception $e) {
            $errores[] = "Fila {$fila_num}: " . $e->getMessage();
        }
    }

    // Marcar ausentes como al_dia
    foreach ($legajos_ausentes as $legajo_ausente) {
        // Solo insertamos si la última gestión NO es ya 'al_dia'
        $stmt_check = $pdo->prepare(
            "SELECT estado FROM gestiones_historial 
             WHERE legajo = ? ORDER BY fecha_gestion DESC LIMIT 1"
        );
        $stmt_check->execute([$legajo_ausente]);
        $ultimo_estado = $stmt_check->fetchColumn();

        if ($ultimo_estado !== 'al_dia') {
            $stmt_al_dia->execute([$legajo_ausente, $_SESSION['user_id']]);
            $count_al_dia++;
        }
    }

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'count' => 'Error crítico: ' . $e->getMessage()]);
    exit;
}

$msg = "{$count_upsert} clientes actualizados/importados.";
if ($count_al_dia > 0) {
    $msg .= " {$count_al_dia} marcados como al día (no aparecen en el CSV).";
}
if (!empty($errores)) {
    $msg .= "\n\nAdvertencias:\n" . implode("\n", array_slice($errores, 0, 5));
}

echo json_encode(['success' => true, 'count' => $msg]);
?>