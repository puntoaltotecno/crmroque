<?php
/**
 * ARCHIVO: api_gestion.php
 */
require_once 'db.php';

if (!isset($_SESSION['user_id'])) exit;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? 'insert';
        $rol = $_SESSION['user_rol'];
        $user_id = $_SESSION['user_id'];
        
        // ── ACCIÓN: ELIMINAR ──
        if ($action === 'delete') {
            if ($rol !== 'admin' && $rol !== 'colaborador') {
                throw new Exception("No tienes permisos para eliminar gestiones.");
            }
            $id_gestion = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM gestiones_historial WHERE id = ?");
            $stmt->execute([$id_gestion]);
            echo json_encode(['success' => true]);
            exit;
        }

        // ── ACCIÓN: EDITAR ──
        if ($action === 'edit') {
            $id_gestion = (int)$_POST['id'];
            $estado  = trim($_POST['estado'] ?? 'promesa');
            $fecha_p = !empty($_POST['fecha_promesa']) ? $_POST['fecha_promesa'] : null;
            $monto_p = !empty($_POST['monto_promesa']) ? (float)$_POST['monto_promesa'] : 0;
            $obs     = trim($_POST['observacion'] ?? '');

            if (in_array($estado, ['promesa', 'llamar']) && empty($fecha_p)) {
                $nombre_estado = $estado === 'promesa' ? 'Promesa de Pago' : 'Llamar más tarde';
                throw new Exception("La fecha es obligatoria para el estado '$nombre_estado'.");
            }

            $stmt = $pdo->prepare("SELECT * FROM gestiones_historial WHERE id = ?");
            $stmt->execute([$id_gestion]);
            $gestion = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$gestion) throw new Exception("La gestión no existe.");

            $intentos = (int)($gestion['intentos'] ?? 0);
            $oculta = (int)($gestion['oculta'] ?? 0);

            // Reglas para operadores base
            if ($rol === 'operador') {
                if ($gestion['usuario_id'] != $user_id) {
                    throw new Exception("Solo puedes editar tus propias gestiones.");
                }
                
                $stmt_last = $pdo->prepare("SELECT id FROM gestiones_historial WHERE legajo = ? ORDER BY id DESC LIMIT 1");
                $stmt_last->execute([$gestion['legajo']]);
                $last_id = $stmt_last->fetchColumn();
                
                if ($last_id != $id_gestion) {
                    throw new Exception("Solo puedes editar tu última gestión. No puedes alterar el pasado.");
                }

                if ($intentos >= 3) {
                    throw new Exception("Esta gestión superó el límite de ediciones permitidas.");
                }

                // Sumamos 1 intento
                $intentos++;
                // Si llegó a 3, ocultamos
                if ($intentos >= 3) {
                    $oculta = 1;
                }
            }

            if ($estado === 'al_dia' && $rol === 'operador') {
                throw new Exception("Restricción de seguridad: No puedes clasificar a un cliente como 'Al Día'.");
            }

            $estados_validos = ['promesa','no_responde','no_corresponde','llamar','numero_baja','otro','al_dia','carta'];
            if (!in_array($estado, $estados_validos)) $estado = 'otro';

            $stmt_upd = $pdo->prepare("UPDATE gestiones_historial SET estado=?, fecha_promesa=?, monto_promesa=?, observaciones=?, intentos=?, oculta=? WHERE id=?");
            $stmt_upd->execute([$estado, $fecha_p, $monto_p, $obs, $intentos, $oculta, $id_gestion]);
            
            echo json_encode(['success' => true]);
            exit;
        }

        // ── ACCIÓN: INSERTAR (NUEVA GESTIÓN) ──
        $legajo  = trim($_POST['legajo']        ?? '');
        $estado  = trim($_POST['estado']        ?? 'promesa');
        $fecha_p = !empty($_POST['fecha_promesa']) ? $_POST['fecha_promesa'] : null;
        $monto_p = !empty($_POST['monto_promesa']) ? (float)$_POST['monto_promesa'] : 0;
        $obs     = trim($_POST['observacion']   ?? '');

        if (empty($legajo)) {
            throw new Exception("El cliente no tiene legajo. No se puede guardar la gestión.");
        }

        if (in_array($estado, ['promesa', 'llamar']) && empty($fecha_p)) {
            $nombre_estado = $estado === 'promesa' ? 'Promesa de Pago' : 'Llamar más tarde';
            throw new Exception("La fecha es obligatoria para el estado '$nombre_estado'.");
        }

        $stmt_check = $pdo->prepare("SELECT estado FROM gestiones_historial WHERE legajo = ? ORDER BY id DESC LIMIT 1");
        $stmt_check->execute([$legajo]);
        $estado_actual = $stmt_check->fetchColumn();

        if ($estado_actual === 'al_dia' && $rol === 'operador') {
            throw new Exception("Este cliente se encuentra AL DÍA. No tienes permisos para modificar su estado.");
        }

        if ($estado === 'al_dia' && $rol === 'operador') {
            throw new Exception("Restricción de seguridad: Los operadores no tienen permisos para clasificar a un cliente como 'Al Día'.");
        }

        $estados_validos = ['promesa','no_responde','no_corresponde','llamar','numero_baja','otro','al_dia','carta'];
        if (!in_array($estado, $estados_validos)) $estado = 'otro';

        $stmt = $pdo->prepare(
            "INSERT INTO gestiones_historial 
                (legajo, usuario_id, estado, fecha_promesa, monto_promesa, observaciones, fecha_gestion)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );

        $stmt->execute([
            $legajo, 
            $user_id, 
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
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>