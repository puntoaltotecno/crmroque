<?php
/**
 * ARCHIVO: api_masivo.php
 * Descripción: Ejecuta acciones masivas sobre un conjunto de legajos seleccionados.
 * Acciones disponibles: asignar_operador, cambiar_estado, eliminar
 */
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit;
}

header('Content-Type: application/json');

$accion  = trim($_POST['accion']  ?? '');
$legajos = json_decode($_POST['legajos'] ?? '[]', true);

if (empty($legajos) || !is_array($legajos)) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron legajos.']);
    exit;
}

// Sanitizar legajos
$legajos = array_filter(array_map('trim', $legajos));

try {
    switch ($accion) {

        // ── ASIGNAR OPERADOR ──────────────────────────────────────────────
        case 'asignar_operador':
            $operador_id = (int)($_POST['operador_id'] ?? 0);
            if ($operador_id === 0) {
                // Sin asignar: borrar asignaciones
                $placeholders = implode(',', array_fill(0, count($legajos), '?'));
                $pdo->prepare("DELETE FROM asignaciones WHERE legajo IN ($placeholders)")
                    ->execute(array_values($legajos));
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO asignaciones (legajo, usuario_id) VALUES (?, ?) 
                     ON DUPLICATE KEY UPDATE usuario_id = VALUES(usuario_id)"
                );
                foreach ($legajos as $legajo) {
                    $stmt->execute([$legajo, $operador_id]);
                }
            }
            echo json_encode(['success' => true, 'count' => count($legajos)]);
            break;

        // ── CAMBIAR ESTADO ────────────────────────────────────────────────
        case 'cambiar_estado':
            $estado = trim($_POST['estado'] ?? '');
            $estados_validos = ['promesa','no_responde','no_corresponde','llamar','numero_baja','otro','al_dia'];
            if (!in_array($estado, $estados_validos)) {
                echo json_encode(['success' => false, 'message' => 'Estado no válido.']);
                exit;
            }
            $stmt = $pdo->prepare(
                "INSERT INTO gestiones_historial 
                    (legajo, usuario_id, estado, observaciones, fecha_gestion)
                 VALUES (?, ?, ?, 'Cambio masivo de estado', NOW())"
            );
            foreach ($legajos as $legajo) {
                $stmt->execute([$legajo, $_SESSION['user_id'], $estado]);
            }
            echo json_encode(['success' => true, 'count' => count($legajos)]);
            break;

        // ── ELIMINAR CLIENTES ─────────────────────────────────────────────
        case 'eliminar':
            $pdo->beginTransaction();
            $placeholders = implode(',', array_fill(0, count($legajos), '?'));
            $vals = array_values($legajos);
            $pdo->prepare("DELETE FROM gestiones_historial WHERE legajo IN ($placeholders)")->execute($vals);
            $pdo->prepare("DELETE FROM asignaciones WHERE legajo IN ($placeholders)")->execute($vals);
            $pdo->prepare("DELETE FROM clientes WHERE legajo IN ($placeholders)")->execute($vals);
            $pdo->commit();
            echo json_encode(['success' => true, 'count' => count($legajos)]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>