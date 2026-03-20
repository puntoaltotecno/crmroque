<?php
/**
 * ARCHIVO: api_importar_asignaciones.php
 * Descripción: Importa un CSV (email, legajo) y actualiza la tabla de asignaciones.
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
    
    // Detectamos el delimitador (, o ;)
    $primera_linea = fgets($handle);
    $delimitador = ','; 
    if (strpos($primera_linea, ';') !== false) $delimitador = ';';
    rewind($handle); 
    fgetcsv($handle, 0, $delimitador); // Saltamos los títulos
    
    // Obtenemos los IDs de los usuarios basándonos en su Email
    $stmt = $pdo->query("SELECT id, email FROM usuarios");
    $users = [];
    while($r = $stmt->fetch()) {
        $users[strtolower(trim($r['email']))] = $r['id'];
    }

    $count = 0;
    $errores = 0;
    
    // Si el legajo ya está asignado a otro, ON DUPLICATE lo reasigna automáticamente al nuevo.
    $sql = "INSERT INTO asignaciones (legajo, usuario_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE usuario_id = VALUES(usuario_id)";
    $ins = $pdo->prepare($sql);
    
    $pdo->beginTransaction();
    try {
        while (($d = fgetcsv($handle, 0, $delimitador)) !== FALSE) {
            if (empty(array_filter($d))) continue;
            
            $email = strtolower(trim($d[0])); // Columna A: Email
            $legajo = trim($d[1]);            // Columna B: Legajo
            
            // Si el email existe en el sistema y hay legajo, lo asignamos
            if (isset($users[$email]) && !empty($legajo)) {
                $ins->execute([$legajo, $users[$email]]);
                $count++;
            } else {
                $errores++;
            }
        }
        $pdo->commit();
        
        $msg = "$count legajos vinculados con éxito.";
        if ($errores > 0) $msg .= " ($errores filas ignoradas por no encontrar el email o estar vacías).";
        
        echo json_encode(['success' => true, 'count' => $msg]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => "Error BD: " . $e->getMessage()]);
    }
    fclose($handle);
} else {
    echo json_encode(['success' => false, 'message' => 'No se recibió el archivo.']);
}
?>