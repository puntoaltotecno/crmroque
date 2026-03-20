<?php
/**
 * ARCHIVO: api_gestion.php
 * ARQUITECTURA: estado, fecha_promesa y monto_promesa viven SOLO en gestiones_historial.
 * La tabla clientes ya NO guarda esos campos — se leen siempre desde la última gestión.
 */
require_once 'db.php';

if (!isset($_SESSION['user_id'])) exit;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $legajo  = trim($_POST['legajo']        ?? '');
        $estado  = trim($_POST['estado']        ?? 'promesa');
        $fecha_p = !empty($_POST['fecha_promesa']) ? $_POST['fecha_promesa'] : null;
        $monto_p = !empty($_POST['monto_promesa']) ? (float)$_POST['monto_promesa'] : 0;
        $obs     = trim($_POST['observacion']   ?? '');

        // Validar estado permitido
        $estados_validos = ['promesa','no_responde','no_corresponde','llamar','numero_baja','otro'];
        if (!in_array($estado, $estados_validos)) $estado = 'otro';

        if (empty($legajo)) {
            throw new Exception("El cliente no tiene legajo. No se puede guardar la gestión.");
        }

        // Insertamos SOLO en el historial — estado/fecha/monto ya no se duplican en clientes
        $stmt = $pdo->prepare(
            "INSERT INTO gestiones_historial 
                (legajo, usuario_id, estado, fecha_promesa, monto_promesa, observaciones, fecha_gestion)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $legajo,
            $_SESSION['user_id'],
            $estado,
            $fecha_p,
            $monto_p,
            $obs
        ]);

        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}
?>