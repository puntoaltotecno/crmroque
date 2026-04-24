<?php
/**
 * ARCHIVO: api_abm_clientes.php
 * DESCRIPCIÓN: ABM completo de clientes para Admin y Colaborador.
 *              Permite Crear, Editar, Eliminar y gestionar el estado de clientes.
 */
require_once 'db.php';

ob_start();
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

// Verificación de sesión y permisos
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Sesión expirada.']);
    exit;
}

$rol = $_SESSION['user_rol'];
$user_id = $_SESSION['user_id'];

// Solo Admin y Colaborador pueden usar este endpoint
if ($rol !== 'admin' && $rol !== 'colaborador') {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para esta acción.']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

try {

    // ── GET: Obtener datos de un cliente por legajo ──
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get') {
        $legajo = trim($_GET['legajo'] ?? '');
        if (empty($legajo)) throw new Exception("Legajo requerido.");

        $stmt = $pdo->prepare("
            SELECT c.*,
                   IFNULL(a.usuario_id, '') as operador_id,
                   u.nombre as operador_nombre,
                   (SELECT estado FROM gestiones_historial g WHERE g.legajo = c.legajo ORDER BY id DESC LIMIT 1) as estado_actual,
                   (SELECT monto_promesa FROM gestiones_historial g WHERE g.legajo = c.legajo ORDER BY id DESC LIMIT 1) as monto_promesa_actual,
                   (SELECT fecha_promesa FROM gestiones_historial g WHERE g.legajo = c.legajo ORDER BY id DESC LIMIT 1) as fecha_promesa_actual
            FROM clientes c
            LEFT JOIN asignaciones a ON c.legajo = a.legajo
            LEFT JOIN usuarios u ON a.usuario_id = u.id
            WHERE c.legajo = ?
        ");
        $stmt->execute([$legajo]);
        $cliente = $stmt->fetch();

        if (!$cliente) throw new Exception("Cliente no encontrado.");

        ob_clean();
        echo json_encode(['success' => true, 'data' => $cliente], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── POST: Crear nuevo cliente ──
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'crear') {
        $legajo = trim($_POST['legajo'] ?? '');
        if (empty($legajo)) throw new Exception("El campo Legajo es obligatorio.");

        // Verificar que el legajo no exista
        $chk = $pdo->prepare("SELECT id FROM clientes WHERE legajo = ?");
        $chk->execute([$legajo]);
        if ($chk->fetch()) throw new Exception("El legajo '$legajo' ya existe en la base de datos.");

        $razon_social  = strtoupper(trim($_POST['razon_social']  ?? ''));
        $nro_documento = strtoupper(trim($_POST['nro_documento'] ?? ''));
        $total_vencido = !empty($_POST['total_vencido']) ? (float)str_replace(',', '.', $_POST['total_vencido']) : 0;
        $dias_atraso   = (int)($_POST['dias_atraso'] ?? 0);
        $c_cuotas      = (int)($_POST['c_cuotas'] ?? 0);
        $sucursal      = trim($_POST['sucursal'] ?? '');
        $localidad     = trim($_POST['localidad'] ?? '');
        $domicilio     = trim($_POST['domicilio'] ?? '');
        $telefonos     = trim($_POST['telefonos'] ?? '');
        $ultimo_pago   = !empty($_POST['ultimo_pago'])  ? $_POST['ultimo_pago']  : null;
        $vencimiento   = !empty($_POST['vencimiento'])  ? $_POST['vencimiento']  : null;
        $l_entidad_id  = !empty($_POST['l_entidad_id']) ? (int)$_POST['l_entidad_id'] : null;
        $moto          = (isset($_POST['moto']) && $_POST['moto'] == '1') ? 1 : 0;

        if (empty($razon_social)) throw new Exception("La Razón Social es obligatoria.");

        $stmt = $pdo->prepare("
            INSERT INTO clientes 
                (l_entidad_id, legajo, razon_social, nro_documento, ultimo_pago, c_cuotas,
                 localidad, domicilio, dias_atraso, total_vencido, vencimiento, sucursal, telefonos, moto)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $l_entidad_id, $legajo, $razon_social, $nro_documento, $ultimo_pago,
            $c_cuotas, $localidad, $domicilio, $dias_atraso, $total_vencido,
            $vencimiento, $sucursal, $telefonos, $moto
        ]);

        // Asignar operador si se indicó
        $operador_id = !empty($_POST['operador_id']) ? (int)$_POST['operador_id'] : null;
        if ($operador_id) {
            $pdo->prepare("INSERT INTO asignaciones (legajo, usuario_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE usuario_id = VALUES(usuario_id)")
                ->execute([$legajo, $operador_id]);
        }

        // Registrar estado inicial si se indica
        $estado_inicial = trim($_POST['estado_inicial'] ?? '');
        $estados_validos = ['promesa','no_responde','no_corresponde','llamar','numero_baja','otro','al_dia','carta'];
        if (!empty($estado_inicial) && in_array($estado_inicial, $estados_validos)) {
            $fecha_p = !empty($_POST['fecha_promesa']) ? $_POST['fecha_promesa'] : null;
            $monto_p = !empty($_POST['monto_promesa']) ? (float)$_POST['monto_promesa'] : 0;
            $obs     = trim($_POST['observacion'] ?? 'Alta manual de cliente.');
            $pdo->prepare("
                INSERT INTO gestiones_historial (legajo, usuario_id, estado, fecha_promesa, monto_promesa, observaciones, fecha_gestion)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ")->execute([$legajo, $user_id, $estado_inicial, $fecha_p, $monto_p, $obs]);
        }

        ob_clean();
        echo json_encode(['success' => true, 'message' => "Cliente '$legajo' creado correctamente.", 'legajo' => $legajo], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── POST: Editar cliente existente ──
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'editar') {
        $legajo = trim($_POST['legajo'] ?? '');
        if (empty($legajo)) throw new Exception("Legajo requerido.");

        $razon_social  = strtoupper(trim($_POST['razon_social']  ?? ''));
        $nro_documento = strtoupper(trim($_POST['nro_documento'] ?? ''));
        $total_vencido = !empty($_POST['total_vencido']) ? (float)str_replace(',', '.', $_POST['total_vencido']) : 0;
        $dias_atraso   = (int)($_POST['dias_atraso'] ?? 0);
        $c_cuotas      = (int)($_POST['c_cuotas'] ?? 0);
        $sucursal      = trim($_POST['sucursal'] ?? '');
        $localidad     = trim($_POST['localidad'] ?? '');
        $domicilio     = trim($_POST['domicilio'] ?? '');
        $telefonos     = trim($_POST['telefonos'] ?? '');
        $ultimo_pago   = !empty($_POST['ultimo_pago'])  ? $_POST['ultimo_pago']  : null;
        $vencimiento   = !empty($_POST['vencimiento'])  ? $_POST['vencimiento']  : null;
        $l_entidad_id  = !empty($_POST['l_entidad_id']) ? (int)$_POST['l_entidad_id'] : null;
        $moto          = (isset($_POST['moto']) && $_POST['moto'] == '1') ? 1 : 0;

        if (empty($razon_social)) throw new Exception("La Razón Social es obligatoria.");

        $stmt = $pdo->prepare("
            UPDATE clientes SET
                l_entidad_id = ?, razon_social = ?, nro_documento = ?, ultimo_pago = ?,
                c_cuotas = ?, localidad = ?, domicilio = ?, dias_atraso = ?,
                total_vencido = ?, vencimiento = ?, sucursal = ?, telefonos = ?, moto = ?
            WHERE legajo = ?
        ");
        $stmt->execute([
            $l_entidad_id, $razon_social, $nro_documento, $ultimo_pago,
            $c_cuotas, $localidad, $domicilio, $dias_atraso,
            $total_vencido, $vencimiento, $sucursal, $telefonos, $moto, $legajo
        ]);

        // Actualizar asignación
        if (isset($_POST['operador_id'])) {
            $operador_id = !empty($_POST['operador_id']) ? (int)$_POST['operador_id'] : null;
            if ($operador_id) {
                $pdo->prepare("INSERT INTO asignaciones (legajo, usuario_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE usuario_id = VALUES(usuario_id)")
                    ->execute([$legajo, $operador_id]);
            } else {
                $pdo->prepare("DELETE FROM asignaciones WHERE legajo = ?")->execute([$legajo]);
            }
        }

        // Registrar nueva gestión si se solicita
        $nuevo_estado = trim($_POST['nuevo_estado'] ?? '');
        $estados_validos = ['promesa','no_responde','no_corresponde','llamar','numero_baja','otro','al_dia','carta'];
        if (!empty($nuevo_estado) && in_array($nuevo_estado, $estados_validos)) {
            $fecha_p = !empty($_POST['fecha_promesa']) ? $_POST['fecha_promesa'] : null;
            $monto_p = !empty($_POST['monto_promesa']) ? (float)$_POST['monto_promesa'] : 0;
            $obs     = trim($_POST['observacion'] ?? 'Modificación manual de datos del cliente.');
            $pdo->prepare("
                INSERT INTO gestiones_historial (legajo, usuario_id, estado, fecha_promesa, monto_promesa, observaciones, fecha_gestion)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ")->execute([$legajo, $user_id, $nuevo_estado, $fecha_p, $monto_p, $obs]);
        }

        ob_clean();
        echo json_encode(['success' => true, 'message' => "Cliente '$legajo' actualizado correctamente."], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── POST: Eliminar cliente (solo Admin) ──
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'eliminar') {
        if ($rol !== 'admin') throw new Exception("Solo los Administradores pueden eliminar clientes.");

        $legajo = trim($_POST['legajo'] ?? '');
        if (empty($legajo)) throw new Exception("Legajo requerido.");

        $confirmacion = trim($_POST['confirmacion'] ?? '');
        if ($confirmacion !== $legajo) throw new Exception("La confirmación no coincide con el legajo. Operación cancelada.");

        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM gestiones_historial WHERE legajo = ?")->execute([$legajo]);
        $pdo->prepare("DELETE FROM asignaciones WHERE legajo = ?")->execute([$legajo]);
        $pdo->prepare("DELETE FROM clientes WHERE legajo = ?")->execute([$legajo]);
        $pdo->commit();

        ob_clean();
        echo json_encode(['success' => true, 'message' => "Cliente '$legajo' eliminado permanentemente."], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── POST: Cambiar estado rápido (insertar gestión) ──
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'cambiar_estado') {
        $legajo  = trim($_POST['legajo']  ?? '');
        $estado  = trim($_POST['estado']  ?? '');
        $obs     = trim($_POST['observacion'] ?? 'Cambio manual de estado por administración.');
        $fecha_p = !empty($_POST['fecha_promesa']) ? $_POST['fecha_promesa'] : null;
        $monto_p = !empty($_POST['monto_promesa']) ? (float)$_POST['monto_promesa'] : 0;

        $estados_validos = ['promesa','no_responde','no_corresponde','llamar','numero_baja','otro','al_dia','carta'];
        if (!in_array($estado, $estados_validos)) throw new Exception("Estado inválido.");
        if (empty($legajo)) throw new Exception("Legajo requerido.");

        $pdo->prepare("
            INSERT INTO gestiones_historial (legajo, usuario_id, estado, fecha_promesa, monto_promesa, observaciones, fecha_gestion)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ")->execute([$legajo, $user_id, $estado, $fecha_p, $monto_p, $obs]);

        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    throw new Exception("Acción '$action' no reconocida.");

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

ob_end_flush();
?>
