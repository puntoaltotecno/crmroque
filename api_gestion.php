<?php
/**
 * ARCHIVO: api_gestion.php
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

        if (empty($legajo)) {
            throw new Exception("El cliente no tiene legajo. No se puede guardar la gestión.");
        }

        // ── REGLA DE NEGOCIO 1: Bloquear edición a operadores si el cliente YA ESTÁ "al_dia" ──
        $stmt_check = $pdo->prepare("SELECT estado FROM gestiones_historial WHERE legajo = ? ORDER BY id DESC LIMIT 1");
        $stmt_check->execute([$legajo]);
        $estado_actual = $stmt_check->fetchColumn();

        if ($estado_actual === 'al_dia' && $_SESSION['user_rol'] === 'operador') {
            throw new Exception("Este cliente se encuentra AL DÍA. No tienes permisos para modificar su estado.");
        }
        // ──────────────────────────────────────────────────────────────────────────────────────

        // ── REGLA DE NEGOCIO 2: Impedir que un operador CAMBIE el estado a "al_dia" ──
        if ($estado === 'al_dia' && $_SESSION['user_rol'] === 'operador') {
            throw new Exception("Restricción de seguridad: Los operadores no tienen permisos para clasificar a un cliente como 'Al Día'.");
        }
        // ─────────────────────────────────────────────────────────────────────────────

        // Validar estado permitido (Incluye 'carta' y 'al_dia')
        $estados_validos = ['promesa','no_responde','no_corresponde','llamar','numero_baja','otro','al_dia','carta'];
        if (!in_array($estado, $estados_validos)) $estado = 'otro';

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
}
?>