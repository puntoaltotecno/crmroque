<?php
/**
 * ARCHIVO: api_importar_csv.php
 *
 * LÓGICA:
 *  1. Lee el CSV y obtiene todos los legajos
 *  2. Para cada fila del CSV:
 *     - Si el legajo YA existe en la BD → actualiza todos los campos MENOS el legajo
 *     - Si el legajo NO existe → lo inserta como cliente nuevo
 *  3. Los legajos que están en la BD pero NO en el CSV →
 *     se les agrega una gestión "al_dia" en el historial (solo si su último estado no era ya "al_dia")
 *
 * COLUMNAS DEL CSV (índice base 0):
 *  0  l_entidad_id
 *  1  legajo          ← CLAVE PRINCIPAL
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
function crm_fecha($raw) {
    $raw = trim($raw);
    if (empty($raw) || strtolower($raw) === 'null') return null;
    $solo = explode(' ', $raw)[0];
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $solo)) return $solo;
    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $solo, $m))
        return $m[3].'-'.sprintf('%02d',$m[2]).'-'.sprintf('%02d',$m[1]);
    return null;
}
function crm_monto($raw) {
    $v = trim(str_replace(['$',' '], '', $raw));
    if (strpos($v,'.') !== false && strpos($v,',') !== false) {
        $v = str_replace('.', '', $v);
        $v = str_replace(',', '.', $v);
    } elseif (strpos($v,',') !== false) {
        $v = str_replace(',', '.', $v);
    }
    return (float)$v;
}
function crm_str($t) {
    $t = trim($t);
    return $t === '' ? '' : mb_convert_encoding($t, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
}
// ─────────────────────────────────────────────────────────────────────────

if (!isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'No se recibió el archivo.']);
    exit;
}

// Abrir y detectar delimitador
$handle  = fopen($_FILES['file']['tmp_name'], 'r');
$primera = fgets($handle);
$delim   = (strpos($primera, ';') !== false) ? ';' : ',';
rewind($handle);
fgetcsv($handle, 0, $delim); // saltar encabezado

// ── Paso 1: leer todo el CSV ──────────────────────────────────────────────
$filas_csv      = [];  // legajo => array de datos
while (($d = fgetcsv($handle, 0, $delim)) !== FALSE) {
    if (count(array_filter($d)) === 0) continue;
    $d      = array_pad($d, 13, '');
    $legajo = crm_str($d[1]);
    if ($legajo === '') continue;
    $filas_csv[$legajo] = $d;
}
fclose($handle);

if (empty($filas_csv)) {
    echo json_encode(['success' => false, 'message' => 'El archivo no tiene filas válidas.']);
    exit;
}

// ── Paso 2: legajos que ya están en la BD ────────────────────────────────
$stmt_leg = $pdo->query("SELECT legajo FROM clientes WHERE legajo IS NOT NULL AND legajo != ''");
$legajos_bd = $stmt_leg->fetchAll(PDO::FETCH_COLUMN);   // array plano de strings

// ── Paso 3: preparar queries ─────────────────────────────────────────────

// INSERT para clientes NUEVOS (legajo no existe en BD)
$sql_insert = "INSERT INTO clientes
    (l_entidad_id, legajo, razon_social, nro_documento,
     ultimo_pago, c_cuotas, domicilio,
     dias_atraso, total_vencido, vencimiento, sucursal, telefonos)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";

// UPDATE para clientes EXISTENTES (busca por legajo, actualiza todo menos legajo)
$sql_update = "UPDATE clientes SET
    l_entidad_id  = ?,
    razon_social  = ?,
    nro_documento = ?,
    ultimo_pago   = ?,
    c_cuotas      = ?,
    domicilio     = ?,
    dias_atraso   = ?,
    total_vencido = ?,
    vencimiento   = ?,
    sucursal      = ?,
    telefonos     = ?
    WHERE legajo  = ?";

// Gestión "al_dia" para los ausentes del CSV
$sql_al_dia = "INSERT INTO gestiones_historial
    (legajo, usuario_id, estado, observaciones, fecha_gestion)
    VALUES (?, ?, 'al_dia', 'Marcado automáticamente como al día (no aparece en el CSV)', NOW())";

$stmt_ins    = $pdo->prepare($sql_insert);
$stmt_upd    = $pdo->prepare($sql_update);
$stmt_al_dia = $pdo->prepare($sql_al_dia);

// Convertir a set para búsqueda O(1)
$legajos_bd_set  = array_flip($legajos_bd);
$legajos_csv_set = array_flip(array_keys($filas_csv));

$cnt_insert  = 0;
$cnt_update  = 0;
$cnt_al_dia  = 0;
$errores     = [];

$pdo->beginTransaction();
try {

    // ── Procesar cada fila del CSV ────────────────────────────────────────
    foreach ($filas_csv as $legajo => $d) {

        $entidad_id = (int)preg_replace('/[^0-9]/', '', $d[0]);
        $domicilio  = crm_str($d[7]);
        $localidad  = crm_str($d[6]);
        $domicilio_completo = $domicilio !== '' && $localidad !== ''
            ? "$domicilio - $localidad"
            : ($localidad !== '' ? $localidad : $domicilio);

        $params_comunes = [
            crm_fecha($d[4]),                                     // ultimo_pago
            (int)preg_replace('/[^0-9\-]/', '', $d[5] ?? '0'),   // c_cuotas
            $domicilio_completo,                                   // domicilio
            (int)preg_replace('/[^0-9\-]/', '', $d[8] ?? '0'),   // dias_atraso
            crm_monto($d[9]),                                     // total_vencido
            crm_fecha($d[10]),                                    // vencimiento
            crm_str($d[11]),                                      // sucursal
            crm_str($d[12]),                                      // telefonos
        ];

        try {
            if (isset($legajos_bd_set[$legajo])) {
                // ── ACTUALIZAR cliente existente ──────────────────────────
                $stmt_upd->execute(array_merge(
                    [$entidad_id, crm_str($d[2]), crm_str($d[3])],
                    $params_comunes,
                    [$legajo]
                ));
                $cnt_update++;
            } else {
                // ── INSERTAR cliente nuevo ────────────────────────────────
                $stmt_ins->execute(array_merge(
                    [$entidad_id, $legajo, crm_str($d[2]), crm_str($d[3])],
                    $params_comunes
                ));
                $cnt_insert++;
            }
        } catch (Exception $ex) {
            $errores[] = "Legajo $legajo: " . $ex->getMessage();
        }
    }

    // ── Marcar como "al_dia" los que no están en el CSV ──────────────────
    $legajos_ausentes = array_diff($legajos_bd, array_keys($filas_csv));

    foreach ($legajos_ausentes as $leg_ausente) {
        // Verificar si su último estado ya es "al_dia" para no duplicar
        $s = $pdo->prepare(
            "SELECT estado FROM gestiones_historial
             WHERE legajo = ? ORDER BY fecha_gestion DESC LIMIT 1"
        );
        $s->execute([$leg_ausente]);
        $ultimo = $s->fetchColumn();

        if ($ultimo !== 'al_dia') {
            $stmt_al_dia->execute([$leg_ausente, $_SESSION['user_id']]);
            $cnt_al_dia++;
        }
    }

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'count' => 'Error crítico: ' . $e->getMessage()]);
    exit;
}

// ── Respuesta ─────────────────────────────────────────────────────────────
$msg = "{$cnt_update} actualizados, {$cnt_insert} nuevos.";
if ($cnt_al_dia > 0)   $msg .= " {$cnt_al_dia} marcados como al día.";
if (!empty($errores))  $msg .= "\n\nAdvertencias:\n" . implode("\n", array_slice($errores, 0, 5));

echo json_encode(['success' => true, 'count' => $msg]);
?>