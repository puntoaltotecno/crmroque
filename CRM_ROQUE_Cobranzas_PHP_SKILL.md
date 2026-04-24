---
name: CRM.ROQUE — Gestión de Cobranzas PHP
description: >-
  Skill especializado para el desarrollo y mejora del sistema CRM.ROQUE (gestión de cobranzas).
  USE ESTE SKILL SIEMPRE que:
  - Crees o edites un archivo api_*.php (cualquier endpoint)
  - Trabajes con CSV, importación masiva o procesamiento de datos
  - Necesites manejar gestiones_historial, asignaciones, clientes
  - Resuelvas problemas en Hostinger (buffer, UTF-8, zona horaria)
  - Implementes validación de roles (admin, colaborador, operador)
  - Hagas queries complejas con prepared statements
  - Necesites ejemplos de transacciones, rollback o eliminación lógica
  - Menciones "legajo" como clave de negocio
  - Trabajes con SELECTs complejos (JOINs, subconsultas, window functions)
  
  INCLUYE: Patrones base, templates de APIs comunes, reglas de negocio, 
  validaciones de seguridad, y ejemplos listos para copiar-pegar.
compatibility: PHP 7.4+, MySQL 11.8+, MariaDB, PDO, Hostinger/XAMPP
---

# CRM.ROQUE — Gestión de Cobranzas PHP

## 📋 Índice Rápido
1. [Estructura Base](#1-estructura-base-de-api)
2. [Patrones de Negocio](#2-patrones-de-negocio)
3. [Validación de Roles](#3-validación-de-roles)
4. [Queries Comunes](#4-queries-comunes)
5. [Transacciones & Rollback](#5-transacciones--rollback)
6. [CSV & Importación](#6-csv--importación-masiva)
7. [Templates Listos](#7-templates-listos-para-copiar)
8. [Debugging Hostinger](#8-debugging-hostinger)
9. [Checklist Deploy](#9-checklist-pre-deploy)

---

## 1. Estructura Base de API

### Patrón Estándar para Cualquier `api_*.php`

```php
<?php
/**
 * ARCHIVO: api_accion.php
 * DESCRIPCIÓN: [Qué hace este endpoint]
 * MÉTODOS: GET|POST
 * RESPUESTA: JSON
 */

// 1️⃣ BUFFER LIMPIO (Hostinger)
ob_start();
require_once 'db.php';

// 2️⃣ VALIDACIÓN SESIÓN (Antes de cualquier salida)
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Sesión expirada']);
    exit;
}

// 3️⃣ HEADERS JSON (Después de validación)
ob_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// 4️⃣ VARIABLES LOCALES
$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$rol = $_SESSION['user_rol'] ?? 'operador';
$user_id = (int)$_SESSION['user_id'];

try {
    // 5️⃣ LÓGICA PRINCIPAL
    if ($action === 'list') {
        $datos = $pdo->query("SELECT * FROM clientes LIMIT 100")->fetchAll();
        echo json_encode(['success' => true, 'data' => $datos], JSON_UNESCAPED_UNICODE);
    } 
    elseif ($action === 'save') {
        // Validar rol
        if ($rol !== 'admin') {
            throw new Exception("No tienes permisos para guardar.");
        }
        
        $nombre = trim($_POST['nombre'] ?? '');
        if (empty($nombre)) throw new Exception("El nombre es requerido.");
        
        // Prepared statement (OBLIGATORIO)
        $stmt = $pdo->prepare("INSERT INTO clientes (legajo, razon_social) VALUES (?, ?)");
        $stmt->execute([$_POST['legajo'], $nombre]);
        
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);
    }
    else {
        throw new Exception("Acción no reconocida: $action");
    }

} catch (Exception $e) {
    // 6️⃣ MANEJO DE ERRORES
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'code' => 'ERROR'
    ], JSON_UNESCAPED_UNICODE);
}
// 7️⃣ NO NECESITA ob_end_flush()
?>
```

**Puntos Críticos:**
- ✅ `ob_start()` → `require_once` → `ob_clean()` (orden exacto)
- ✅ Validación de sesión ANTES de headers
- ✅ `JSON_UNESCAPED_UNICODE` para tildes y caracteres especiales
- ✅ Try/catch alrededor de toda la lógica
- ✅ Prepared statements con `?` o `:marcador`
- ✅ Sin `echo` antes de headers

---

## 2. Patrones de Negocio

### Patrón A: Operador ve solo su cartera

```php
// Variable local
$can_see_all = ($rol === 'admin' || $rol === 'colaborador');
$uid = $_SESSION['user_id'];

// Construcción dinámica del WHERE
$where = "1=1";
if (!$can_see_all) {
    $where .= " AND asignaciones.usuario_id = :uid";
    $params[':uid'] = $uid;
}

$sql = "SELECT c.* FROM clientes c 
        LEFT JOIN asignaciones a ON c.legajo = a.legajo 
        WHERE $where";
        
$stmt = $pdo->prepare($sql);
$stmt->execute($params ?? []);
```

### Patrón B: Cliente "Al Día" bloquea edición para operador

```php
// Fetch estado actual del cliente
$stmt = $pdo->prepare("SELECT estado FROM gestiones_historial WHERE legajo = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$legajo]);
$estado_actual = $stmt->fetchColumn();

// Regla de negocio
if ($estado_actual === 'al_dia' && $rol === 'operador') {
    throw new Exception("Este cliente está AL DÍA. No puedes modificar su estado.");
}
```

### Patrón C: Operador NO puede clasificar como "Al Día"

```php
// Validación explícita
if ($rol === 'operador' && $estado === 'al_dia') {
    throw new Exception("Restricción de seguridad: Los operadores no pueden clasificar a clientes como 'Al Día'.");
}
```

### Patrón D: Eliminación Lógica (no física)

```php
// ❌ NUNCA HAGAS
// DELETE FROM gestiones_historial WHERE id = ?

// ✅ SIEMPRE HAZ
$stmt = $pdo->prepare("UPDATE gestiones_historial SET oculta = 1 WHERE id = ?");
$stmt->execute([$id_gestion]);

// Para ver registros ocultos (solo admin en auditoría)
$where = "oculta = 0 OR oculta IS NULL";
```

### Patrón E: Legajo es la clave de negocio universal

```php
// ✅ CORRECTO: Usar legajo como FK lógica
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE legajo = ?");
$stmt->execute([$legajo]);

// ❌ INCORRECTO: Usar id (puede ser 0)
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");

// NOTA: legajo SIEMPRE debe estar trimmed y validado
$legajo = trim($_POST['legajo'] ?? '');
if (empty($legajo)) throw new Exception("Legajo requerido");
```

---

## 3. Validación de Roles

### Matriz de Permisos

```php
// Definición global de permisos (puede ir en db.php)
$PERMISOS = [
    'admin' => [
        'ver_deuda_total' => true,
        'ver_metrics' => true,
        'importar_csv' => true,
        'eliminar_clientes' => true,
        'crear_usuarios' => true,
        'clasificar_al_dia' => true,
        'ver_ocultas' => true,  // Para auditoría
    ],
    'colaborador' => [
        'ver_deuda_total' => true,
        'ver_metrics' => true,
        'importar_csv' => false,
        'eliminar_clientes' => false,
        'crear_usuarios' => false,
        'clasificar_al_dia' => true,
        'ver_ocultas' => false,
    ],
    'operador' => [
        'ver_deuda_total' => false,  // Oculto para operadores
        'ver_metrics' => false,
        'importar_csv' => false,
        'eliminar_clientes' => false,
        'crear_usuarios' => false,
        'clasificar_al_dia' => false,  // ¡BLOQUEADO!
        'ver_ocultas' => false,
    ],
];

// Validación en API
function verificarPermiso($permiso) {
    global $PERMISOS, $rol;
    if (!$PERMISOS[$rol][$permiso] ?? false) {
        throw new Exception("No tienes permiso: $permiso");
    }
}

// Usar en código
try {
    verificarPermiso('eliminar_clientes');
    // ... ejecutar eliminación ...
} catch (Exception $e) {
    throw $e;
}
```

### Patrones de Validación Comunes

```php
// Tipo 1: Solo Admin
if ($rol !== 'admin') {
    throw new Exception("Solo los administradores pueden hacer esto.");
}

// Tipo 2: Admin o Colaborador
if ($rol !== 'admin' && $rol !== 'colaborador') {
    throw new Exception("No tienes permisos para esta acción.");
}

// Tipo 3: Operador ve solo su cartera
if ($rol === 'operador' && $asignado_a !== $user_id) {
    throw new Exception("Solo puedes editar tu propia cartera.");
}

// Tipo 4: Bloqueo específico de acción
if ($rol === 'operador' && $estado === 'al_dia') {
    throw new Exception("Restricción de seguridad: Los operadores no pueden clasificar como 'Al Día'.");
}
```

---

## 4. Queries Comunes

### SELECT A: Obtener estado actual del cliente

```php
// Forma 1: Subquery (una línea)
$sql = "SELECT 
            c.*,
            (SELECT estado FROM gestiones_historial WHERE legajo = c.legajo ORDER BY id DESC LIMIT 1) as estado_actual
        FROM clientes c
        WHERE c.legajo = ?";

// Forma 2: LEFT JOIN con ROW_NUMBER (más eficiente)
$sql = "SELECT c.*, g.estado as estado_actual
        FROM clientes c
        LEFT JOIN (
            SELECT legajo, estado, ROW_NUMBER() OVER (PARTITION BY legajo ORDER BY id DESC) as rn
            FROM gestiones_historial
        ) g ON c.legajo = g.legajo AND g.rn = 1
        WHERE c.legajo = ?";
```

### SELECT B: Listar clientes con filtros dinámicos

```php
$where = "1=1";
$params = [];

// Búsqueda por texto
if (!empty($_GET['q'])) {
    $where .= " AND (c.razon_social LIKE :q OR c.legajo LIKE :q OR c.nro_documento LIKE :q)";
    $params[':q'] = "%{$_GET['q']}%";
}

// Filtro por estado
if (!empty($_GET['estado'])) {
    if ($_GET['estado'] === 'sin_gestion') {
        $where .= " AND gest.estado IS NULL";
    } else {
        $where .= " AND gest.estado = :estado";
        $params[':estado'] = $_GET['estado'];
    }
}

// Filtro por operador asignado
if (!empty($_GET['operador_id'])) {
    if ($_GET['operador_id'] == -1) {
        $where .= " AND a.usuario_id IS NULL";
    } else {
        $where .= " AND a.usuario_id = :op_id";
        $params[':op_id'] = (int)$_GET['operador_id'];
    }
}

// Ejecutar
$sql = "SELECT c.*, a.usuario_id FROM clientes c
        LEFT JOIN asignaciones a ON c.legajo = a.legajo
        LEFT JOIN gestiones_historial gest ON c.legajo = gest.legajo
        WHERE $where
        ORDER BY c.dias_atraso ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
```

### SELECT C: Estadísticas por operador

```php
$sql = "SELECT 
            u.nombre,
            (SELECT COUNT(*) FROM asignaciones a WHERE a.usuario_id = u.id) as total_asignados,
            (SELECT COUNT(DISTINCT h.legajo) FROM gestiones_historial h WHERE h.usuario_id = u.id) as clientes_gestionados,
            (SELECT COUNT(DISTINCT h2.legajo) FROM gestiones_historial h2 WHERE h2.usuario_id = u.id AND h2.estado = 'promesa') as promesas_logradas
        FROM usuarios u
        WHERE u.rol IN ('operador', 'colaborador')
        ORDER BY total_asignados DESC";

$stmt = $pdo->query($sql);
$operadores = $stmt->fetchAll();

// Calcular efectividad
foreach ($operadores as &$op) {
    $op['efectividad'] = $op['clientes_gestionados'] > 0 
        ? round(($op['promesas_logradas'] / $op['clientes_gestionados']) * 100)
        : 0;
}
```

### SELECT D: Gestiones con búsqueda de ocultas (Admin)

```php
$ver_ocultas = (isset($_GET['ver_ocultas']) && $_GET['ver_ocultas'] === '1') ? 1 : 0;
$can_see_hidden = ($rol === 'admin');

// Solo admin puede ver ocultas
if (!$can_see_hidden) {
    $ver_ocultas = 0;
}

$where = "g.legajo = ?";
if (!$ver_ocultas) {
    $where .= " AND (g.oculta = 0 OR g.oculta IS NULL)";
}

$sql = "SELECT g.*, u.nombre as operador
        FROM gestiones_historial g
        LEFT JOIN usuarios u ON g.usuario_id = u.id
        WHERE $where
        ORDER BY g.fecha_gestion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$legajo]);
```

---

## 5. Transacciones & Rollback

### Patrón Base

```php
try {
    $pdo->beginTransaction();
    
    // Operación 1
    $stmt1 = $pdo->prepare("UPDATE clientes SET total_vencido = ? WHERE legajo = ?");
    $stmt1->execute([0, $legajo]);
    
    // Operación 2 (si falla, todo revierte)
    $stmt2 = $pdo->prepare("INSERT INTO gestiones_historial (legajo, estado) VALUES (?, 'al_dia')");
    $stmt2->execute([$legajo]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
```

### Patrón para Operaciones Masivas

```php
$legajos = json_decode($_POST['legajos'] ?? '[]', true);
if (empty($legajos)) throw new Exception("No se recibieron legajos.");

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("UPDATE clientes SET estado = ? WHERE legajo = ?");
    
    foreach ($legajos as $leg) {
        $stmt->execute(['al_dia', $leg]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'count' => count($legajos)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
}
```

---

## 6. CSV — Importación Masiva

### Patrón Robusto

```php
function importarCSV($archivo_tmp) {
    global $pdo;
    
    $handle = fopen($archivo_tmp, 'r');
    
    // 1️⃣ DETECCIÓN AUTOMÁTICA DE DELIMITADOR
    $primera_linea = fgets($handle);
    $primera_linea = preg_replace('/\x{FEFF}/u', '', $primera_linea);  // Limpiar BOM
    $delimitador = (strpos($primera_linea, ';') !== false) ? ';' : ',';
    
    rewind($handle);
    
    // 2️⃣ LECTURA DE CABECERAS
    $headers = fgetcsv($handle, 0, $delimitador);
    $headers = array_map('strtolower', array_map('trim', $headers));
    
    $idx_legajo = array_search('legajo', $headers);
    $idx_razon = array_search('razon_social', $headers);
    $idx_vencido = array_search('total_vencido', $headers);
    
    // 3️⃣ TRANSACCIÓN MASIVA
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO clientes (legajo, razon_social, total_vencido)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
            razon_social = VALUES(razon_social),
            total_vencido = VALUES(total_vencido)
        ");
        
        $contador = 0;
        while (($row = fgetcsv($handle, 0, $delimitador)) !== false) {
            if (empty(array_filter($row))) continue;
            
            $legajo = trim($row[$idx_legajo] ?? '');
            if (!empty($legajo)) {
                $stmt->execute([
                    $legajo,
                    trim($row[$idx_razon] ?? ''),
                    (float)$row[$idx_vencido] ?? 0
                ]);
                $contador++;
            }
        }
        
        $pdo->commit();
        return ['success' => true, 'count' => $contador];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    } finally {
        fclose($handle);
    }
}
```

---

## 7. Templates Listos para Copiar

### Template 1: api_buscar.php

```php
<?php
ob_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) { ob_clean(); header('Content-Type: application/json'); echo json_encode(['error' => 'Sesión expirada']); exit; }

ob_clean();
header('Content-Type: application/json; charset=utf-8');

try {
    $q = $_GET['q'] ?? '';
    $estado = $_GET['estado'] ?? '';
    
    $where = "1=1";
    $params = [];
    
    if (!empty($q)) {
        $where .= " AND (c.razon_social LIKE :q OR c.legajo LIKE :q)";
        $params[':q'] = "%$q%";
    }
    
    if (!empty($estado)) {
        $where .= " AND gest.estado = :estado";
        $params[':estado'] = $estado;
    }
    
    $sql = "SELECT c.* FROM clientes c LEFT JOIN gestiones_historial gest ON c.legajo = gest.legajo WHERE $where LIMIT 50";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
```

### Template 2: api_guardar_gestion.php

```php
<?php
ob_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) exit;

ob_clean();
header('Content-Type: application/json; charset=utf-8');

$rol = $_SESSION['user_rol'];
$user_id = $_SESSION['user_id'];

try {
    $action = $_POST['action'] ?? 'insert';
    $legajo = trim($_POST['legajo'] ?? '');
    $estado = trim($_POST['estado'] ?? 'promesa');
    $fecha_p = !empty($_POST['fecha_promesa']) ? $_POST['fecha_promesa'] : null;
    $monto_p = !empty($_POST['monto_promesa']) ? (float)$_POST['monto_promesa'] : 0;
    $obs = trim($_POST['observacion'] ?? '');
    
    if (empty($legajo)) throw new Exception("Legajo requerido");
    
    if (in_array($estado, ['promesa', 'llamar']) && empty($fecha_p)) {
        throw new Exception("Fecha requerida para estado '$estado'");
    }
    
    if ($rol === 'operador' && $estado === 'al_dia') {
        throw new Exception("No puedes clasificar como 'Al Día'");
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO gestiones_historial 
        (legajo, usuario_id, estado, fecha_promesa, monto_promesa, observaciones, fecha_gestion)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([$legajo, $user_id, $estado, $fecha_p, $monto_p, $obs]);
    
    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
```

### Template 3: api_importar.php

```php
<?php
ob_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'No se recibió archivo']);
    exit;
}

$handle = fopen($_FILES['file']['tmp_name'], 'r');
$primera_linea = fgets($handle);
$delimitador = strpos($primera_linea, ';') !== false ? ';' : ',';
rewind($handle);

$headers = fgetcsv($handle, 0, $delimitador);
$headers = array_map('strtolower', array_map('trim', $headers));

$idx_legajo = array_search('legajo', $headers);
$idx_razon = array_search('razon_social', $headers);

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("INSERT INTO clientes (legajo, razon_social) VALUES (?, ?) ON DUPLICATE KEY UPDATE razon_social = VALUES(razon_social)");
    
    $cnt = 0;
    while (($row = fgetcsv($handle, 0, $delimitador)) !== false) {
        if (empty(array_filter($row))) continue;
        
        $legajo = trim($row[$idx_legajo] ?? '');
        if (!empty($legajo)) {
            $stmt->execute([$legajo, trim($row[$idx_razon] ?? '')]);
            $cnt++;
        }
    }
    
    $pdo->commit();
    ob_clean();
    echo json_encode(['success' => true, 'count' => $cnt], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    $pdo->rollBack();
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

fclose($handle);
?>
```

---

## 8. Debugging Hostinger

### Problema A: "Unexpected end of JSON input"

**Síntoma:** El frontend recibe respuesta vacía o HTML en lugar de JSON

**Causa:** Output antes del JSON (espacios en blanco, errors PHP, BOM)

**Solución:**
```php
// 1. Verificar ob_start() y ob_clean()
ob_start();
require_once 'db.php';
// ... código ...
ob_clean();
header('Content-Type: application/json; charset=utf-8');
echo json_encode($data);
// NO ob_end_flush();

// 2. Verificar no hay espacios tras ?>
// ❌ MALO:
// ?>
// (espacio vacío aquí)

// ✅ BUENO: No cerrar etiqueta PHP si es último en archivo
// ?>  ← NO ESCRIBIR ESTO

// 3. Verificar no hay error_reporting mostrando errores
ini_set('display_errors', 0);
error_reporting(0);
```

### Problema B: Caracteres especiales (tildes, ñ, ü)

**Síntoma:** Las tildes aparecen como ?, caracteres raros

**Solución:**
```php
// En db.php
$pdo = new PDO(
    "mysql:host=$host;dbname=$db;charset=utf8mb4",
    $user,
    $pass
);

// En endpoints
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

// En HTML
<meta charset="UTF-8">
```

### Problema C: Zona horaria desfasada

**Síntoma:** Las fechas guardadas están 3 horas adelantadas

**Solución:**
```php
// En db.php
date_default_timezone_set('America/Argentina/Buenos_Aires');
$pdo->exec("SET time_zone = '-03:00'");
```

---

## 9. Checklist Pre-Deploy

Antes de hacer push a Hostinger:

- [ ] ¿Todos los `api_*.php` tienen `ob_start()` → `ob_clean()` correcto?
- [ ] ¿Valida sesión ANTES de headers?
- [ ] ¿Todos los SQL usan prepared statements con `?` o `:marcador`?
- [ ] ¿JSON responses tienen `JSON_UNESCAPED_UNICODE`?
- [ ] ¿No hay spaces tras `?>`?
- [ ] ¿db.php fuerza zona horaria Argentina?
- [ ] ¿db.php usa `charset=utf8mb4`?
- [ ] ¿Roles están validados ANTES de operaciones sensibles?
- [ ] ¿Transacciones tienen `beginTransaction()` + `commit()`/`rollBack()`?
- [ ] ¿Eliminaciones usan UPDATE `oculta=1` (no DELETE)?
- [ ] ¿Error handling tiene try/catch en toda la lógica?
- [ ] ¿CSV importación detecta delimitador + limpia BOM?
- [ ] ¿Preparar statement en loops (no dentro del loop)?
- [ ] ¿WHERE dinámicos construyen string seguramente?
- [ ] ¿No hay `echo` antes de headers?

---

## 10. Errores Comunes & Soluciones

| Error | Causa | Solución |
|-------|-------|----------|
| "Unexpected end of JSON" | Output antes del JSON | `ob_clean()` después de `require` |
| Tildes aparecer como `?` | Falta `charset=utf8mb4` | Verificar PDO + `JSON_UNESCAPED_UNICODE` |
| SQL injection | String concatenado | Usar prepared statements `?` |
| Operador ve datos de otro | No validar usuario_id | Agregar `WHERE usuario_id = :uid` |
| Transacción no revierte | Falta `rollBack()` | Envolver en try/catch |
| Fecha con 3 horas de diferencia | Zona horaria | Ejecutar `SET time_zone` en db.php |
| CSV con caracteres rotos | No limpiar BOM | `preg_replace('/\x{FEFF}/u', '', ...)` |
| `lastInsertId()` retorna 0 | Usar con ON DUPLICATE KEY | No usar, mejor hacer SELECT después |
| Query lenta con muchos registros | No hay índices | Crear índice en `legajo` |

---

## 11. Referencias Rápidas

### Constantes de Estados
```php
const ESTADOS_VALIDOS = [
    'promesa', 'no_responde', 'no_corresponde', 'llamar',
    'numero_baja', 'otro', 'al_dia', 'carta'
];

if (!in_array($estado, ESTADOS_VALIDOS)) {
    $estado = 'otro';
}
```

### Estructura Estándar de Respuesta JSON

```php
// ✅ Éxito con datos
['success' => true, 'data' => $resultado, 'count' => 10]

// ✅ Éxito sin datos
['success' => true]

// ✅ Error
['success' => false, 'message' => 'Descripción clara del error']

// ✅ Listado
['success' => true, 'data' => [...], 'total' => 50, 'pagina' => 1]
```

### Headers HTTP Estándar
```php
header('Content-Type: application/json; charset=utf-8');  // OBLIGATORIO
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');  // Opcional pero recomendado
http_response_code(400);  // Para errores (opcional)
```

---

## 12. Recursos Externos

- **Documentación MySQL JOIN:** [mysql.com/docs](https://dev.mysql.com/doc/refman/)
- **PDO Prepared Statements:** [php.net/pdo](https://www.php.net/manual/es/pdo.prepared-statements.php)
- **Zona Horaria Argentina:** `America/Argentina/Buenos_Aires` o UTC-3
- **Charset UTF-8:** Siempre `utf8mb4` (no `utf8`)

---

## 13. Checklist de Desarrollo

Cuando hagas un nuevo `api_*.php`:

1. ✅ Copiar estructura base de la sección 1
2. ✅ Reemplazar `[Qué hace este endpoint]` con descripción real
3. ✅ Definir `$action` y sus casos
4. ✅ Validar rol ANTES de lógica sensible
5. ✅ Usar prepared statements SIEMPRE
6. ✅ Responder JSON con `JSON_UNESCAPED_UNICODE`
7. ✅ Try/catch toda la lógica
8. ✅ Probar en local (XAMPP) primero
9. ✅ Verificar checklist de deploy
10. ✅ Push a Hostinger

---

## 14. Variables y Convenciones

```php
// Session
$_SESSION['user_id']     // (int) ID del usuario logueado
$_SESSION['user_name']   // (string) Nombre completo
$_SESSION['user_rol']    // (string) 'admin', 'colaborador', 'operador'

// POST/GET
$_POST['action']         // Acción a realizar
$_POST['legajo']         // Clave de negocio
$_POST['estado']         // Estado de gestión
$_POST['observacion']    // Notas del operador

// Variables locales
$rol                     // Rol del usuario
$user_id                 // ID del usuario logueado
$pdo                     // Conexión a BD (desde db.php)
```

---

## 15. Patrón Completo (Ejemplo Real)

Ver archivos reales en el proyecto:
- `api_gestion.php` → Estructura completa + validaciones
- `api_clientes.php` → Búsqueda + filtros dinámicos
- `api_dashboard.php` → Transacciones + múltiples queries
- `api_importar_csv.php` → CSV robusto + rollback

Copia estos patrones para cualquier nuevo endpoint. ¡Siempre funcionan en Hostinger!

