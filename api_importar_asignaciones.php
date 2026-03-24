<?php
/**
 * ARCHIVO: api_importar_asignaciones.php
 * Descripción: Importa un CSV inteligente y actualiza la tabla de asignaciones.
 */
require_once 'db.php';
ini_set('auto_detect_line_endings', TRUE);

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}
header('Content-Type: application/json');

if (isset($_FILES['file'])) {
    $handle = fopen($_FILES['file']['tmp_name'], "r");
    
    // 1. Detectamos el delimitador (, o ;)
    $primera_linea = fgets($handle);
    $delimitador = ','; 
    if (strpos($primera_linea, ';') !== false) $delimitador = ';';
    
    rewind($handle); 
    
    // 2. Extraemos los títulos y detectamos las posiciones automáticamente
    $headers = fgetcsv($handle, 0, $delimitador); 
    $headers = array_map('strtolower', array_map('trim', $headers));
    
    $idx_legajo = array_search('legajo', $headers);
    $idx_email = array_search('email', $headers);
    
    // Si por algún motivo el archivo no tiene títulos, asumimos tu formato (Legajo=A, Email=B)
    if ($idx_legajo === false) $idx_legajo = 0;
    if ($idx_email === false) $idx_email = 1;

    // 3. Obtenemos los IDs de los usuarios basándonos en su Email
    $stmt = $pdo->query("SELECT id, email FROM usuarios");
    $users = [];
    while($r = $stmt->fetch()) {
        $users[strtolower(trim($r['email']))] = $r['id'];
    }

    $count = 0;
    $errores = 0;
    
    // 4. Preparamos la inserción (ON DUPLICATE KEY UPDATE pisa la asignación si el cliente ya tenía otro operador)
    $sql = "INSERT INTO asignaciones (legajo, usuario_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE usuario_id = VALUES(usuario_id)";
    $ins = $pdo->prepare($sql);
    
    $pdo->beginTransaction();
    try {
        while (($d = fgetcsv($handle, 0, $delimitador)) !== FALSE) {
            if (empty(array_filter($d))) continue;
            
            // Usamos los índices que detectó el sistema arriba
            $legajo = trim($d[$idx_legajo]);
            $email = strtolower(trim($d[$idx_email]));
            
            // Si el email existe en la BD y la celda legajo no está vacía, lo asignamos
            if (isset($users[$email]) && !empty($legajo)) {
                $ins->execute([$legajo, $users[$email]]);
                $count++;
            } else {
                $errores++;
            }
        }
        $pdo->commit();
        
        $msg = "$count legajos vinculados con éxito.";
        if ($errores > 0) $msg .= " ($errores filas ignoradas por no encontrar el email en el sistema).";
        
        echo json_encode(['success' => true, 'count' => $msg]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error de Base de Datos: ' . $e->getMessage()]);
    }
    fclose($handle);
} else {
    echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo']);
}
?>