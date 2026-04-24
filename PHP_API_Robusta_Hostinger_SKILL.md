---
name: PHP API Robusta Hostinger
description: >-
  Crea endpoints API robustos en PHP optimizados para servidores Hostinger.
  Usa este skill siempre que necesites:
  - Escribir una nueva API o endpoint PHP (cualquier archivo api_*.php)
  - Manejar importación CSV o procesamiento masivo de datos
  - Trabajar con JSON responses y prepared statements
  - Solucionar problemas de encoding UTF-8 o buffer en Hostinger
  - Implementar validación de roles y seguridad en endpoints
  - Manejo de transacciones y rollback en MySQL
  Patrones garantizados: prepared statements obligatorios, buffer limpieza, 
  zona horaria Argentina, respuestas JSON puras, eliminación lógica de datos.
compatibility: PHP 7.4+, MySQL/MariaDB, PDO, Hostinger
---

# PHP API Robusta Hostinger

## 1. Patrón Base de Endpoint Seguro

Todos los endpoints deben seguir esta estructura:

```php
<?php
/**
 * ARCHIVO: api_accion.php
 * DESCRIPCIÓN: [Breve descripción de qué hace]
 */

// 1. LIMPIEZA ABSOLUTA (Hostinger requiere esto)
ob_start();
require_once 'db.php';

// 2. VALIDACIÓN DE SESIÓN (Primero, antes de cualquier salida)
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Sesión expirada']);
    exit;
}

// 3. HEADERS (Después de validación, antes de lógica)
ob_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// 4. VARIABLES LOCALES
$accion = $_GET['action'] ?? ($_POST['action'] ?? 'default');
$rol = $_SESSION['user_rol'] ?? 'operador';
$user_id = (int)$_SESSION['user_id'];

try {
    // 5. LÓGICA PRINCIPAL
    if ($accion === 'ejemplo') {
        // Tu código aquí
        echo json_encode(['success' => true, 'data' => $resultado], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    // 6. MANEJO DE ERRORES
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    // No es necesario ob_end_flush, pero si lo uses, debe ser el último
}
?>
```

**Puntos críticos:**
- `ob_start()` al inicio
- `ob_clean()` después de `require_once`
- `header('Content-Type: application/json; charset=utf-8')` SIEMPRE
- `JSON_UNESCAPED_UNICODE` en `json_encode()`
- Try/catch alrededor de toda la lógica
- Sin `echo` antes de headers

---

## 2. Prepared Statements — El Patrón PDO

**Nunca concatenar variables en SQL.** Siempre usar marcadores:

```php
// ✅ CORRECTO
$stmt = $pdo->prepare("SELECT id, nombre FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// ✅ TAMBIÉN CORRECTO (Nombrado)
$stmt = $pdo->prepare("SELECT id FROM clientes WHERE legajo = :legajo AND estado = :estado");
$stmt->execute([':legajo' => $legajo, ':estado' => $estado]);
$cliente = $stmt->fetchColumn();

// ❌ NUNCA HAGAS ESTO
$sql = "SELECT * FROM clientes WHERE legajo = '$legajo'";  // VULNERABLE
$stmt = $pdo->query($sql);

// ❌ NI ESTO
$sql = "SELECT * FROM clientes WHERE legajo = {$legajo}";  // VULNERABLE
```

**Patrón para múltiples inserciones (CSV/masivo):**
```php
$stmt = $pdo->prepare("INSERT INTO clientes (legajo, razon_social, total_vencido) VALUES (?, ?, ?)");

$pdo->beginTransaction();
try {
    while (($row = fgetcsv($handle)) !== false) {
        $stmt->execute([$row[0], $row[1], $row[2]]);
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
}
```

---

## 3. Buffer & Limpieza (Hostinger específico)

Hostinger tiene reglas estrictas sobre output buffering. **Esto es obligatorio:**

```php
// OPCIÓN A: Limpieza agresiva (recomendado para Hostinger)
ob_start();
require_once 'db.php';

// ... validaciones ...

ob_clean();  // 👈 CRÍTICO: Limpia cualquier output anterior
header('Content-Type: application/json; charset=utf-8');
echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
// NO ob_end_flush() — dejar que PHP lo haga automáticamente

// OPCIÓN B: Si necesitas preservar ciertos buffers
ob_clean();
echo json_encode($respuesta);
ob_end_flush();  // Envía y cierra buffer
```

**Error común:** "Unexpected end of JSON input"
- Causa: Output antes del JSON (espacios, errores PHP, BOM)
- Solución: `ob_clean()` y verificar que no hay espacios tras `?>`

---

## 4. Encoding UTF-8 & Caracteres Especiales

**En db.php:**
```php
$pdo = new PDO(
    "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
    $db_user,
    $db_pass
);
```

**En endpoints:**
```php
// Limpiar BOM invisible (problema común con CSV en Windows)
$data = preg_replace('/\x{FEFF}/u', '', $data);

// Normalizar espacios
$data = trim(preg_replace('/\s+/', ' ', $data));

// json_encode siempre con flag
echo json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
```

---

## 5. Validación de Roles (Seguridad)

Patrón estándar para CRM.ROQUE:

```php
// Roles disponibles: 'admin', 'colaborador', 'operador'
$rol = $_SESSION['user_rol'] ?? 'operador';
$user_id = (int)$_SESSION['user_id'];

// ✅ Solo Admin y Colaborador
if ($rol !== 'admin' && $rol !== 'colaborador') {
    throw new Exception("No tienes permisos para esta acción.");
}

// ✅ Solo Admin (más restrictivo)
if ($rol !== 'admin') {
    throw new Exception("Solo los administradores pueden hacer esto.");
}

// ✅ Operador no puede clasificar como "Al Día" (Restricción Negocio)
if ($rol === 'operador' && $estado === 'al_dia') {
    throw new Exception("Restricción de seguridad: Los operadores no pueden clasificar a clientes como 'Al Día'.");
}

// ✅ Operador solo ve su cartera
if ($rol === 'operador') {
    $where .= " AND asignaciones.usuario_id = :uid";
    $params[':uid'] = $user_id;
}
```

---

## 6. Importación CSV (Patrón Robusto)

```php
function importarCSV($archivo_tmp) {
    global $pdo;
    
    // 1. DETECCIÓN DE DELIMITADOR
    $handle = fopen($archivo_tmp, 'r');
    $primera_linea = fgets($handle);
    $delimitador = (strpos($primera_linea, ';') !== false) ? ';' : ',';
    rewind($handle);
    
    // 2. LECTURA DE CABECERAS
    $headers = fgetcsv($handle, 0, $delimitador);
    $headers = array_map('strtolower', array_map('trim', $headers));
    
    // 3. DETECCIÓN AUTOMÁTICA DE COLUMNAS
    $idx_legajo = array_search('legajo', $headers);
    $idx_email = array_search('email', $headers);
    
    // 4. TRANSACCIÓN MASIVA
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO clientes (legajo, razon_social, ...) VALUES (?, ?, ...)");
        
        while (($row = fgetcsv($handle, 0, $delimitador)) !== false) {
            if (empty(array_filter($row))) continue;
            
            $legajo = trim($row[$idx_legajo]);
            if (!empty($legajo)) {
                $stmt->execute([$legajo, ...]);
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

## 7. Respuestas JSON Estándar

```php
// ✅ ÉXITO (con datos)
echo json_encode([
    'success' => true,
    'data' => $resultado,
    'count' => count($resultado)
], JSON_UNESCAPED_UNICODE);

// ✅ ÉXITO (sin datos)
echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);

// ✅ ERROR
echo json_encode([
    'success' => false,
    'message' => 'Descripción clara del error',
    'code' => 'OPERAND_NOT_ALLOWED'  // Opcional, para debugging
], JSON_UNESCAPED_UNICODE);

// ✅ LISTADOS
echo json_encode([
    'success' => true,
    'data' => $datos,
    'total' => count($datos),
    'pagina' => $pagina,
    'limit' => $limit
], JSON_UNESCAPED_UNICODE);
```

**Frontend espera:**
```javascript
const res = await fetch('api_ejemplo.php');
const d = await res.json();
if (d.success) { /* procesar d.data */ }
else { alert(d.message); }
```

---

## 8. Transacciones & Rollback

```php
// Patrón para operaciones críticas (eliminar, actualizar masivo)
try {
    $pdo->beginTransaction();
    
    // Operación 1
    $stmt1->execute([...]);
    
    // Operación 2 (si falla, rollback automático)
    $stmt2->execute([...]);
    
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
```

---

## 9. Eliminación Lógica (No física)

En CRM.ROQUE, NO borramos datos. Los ocultamos:

```php
// ❌ NUNCA HAGAS
$pdo->prepare("DELETE FROM gestiones_historial WHERE id = ?")->execute([$id]);

// ✅ SIEMPRE HAZ (Eliminación Lógica)
$pdo->prepare("UPDATE gestiones_historial SET oculta = 1 WHERE id = ?")->execute([$id]);

// Para restaurar
$pdo->prepare("UPDATE gestiones_historial SET oculta = 0 WHERE id = ?")->execute([$id]);

// En queries, filtrar registros ocultos
$where = "g.oculta = 0 OR g.oculta IS NULL";
```

---

## 10. Zona Horaria Argentina (db.php)

```php
date_default_timezone_set('America/Argentina/Buenos_Aires');
$pdo->exec("SET time_zone = '-03:00'");  // UTC-3

// Resultado: Las fechas en CURDATE() serán correctas sin desfase
```

---

## 11. Endpoint Checklist

Antes de dar por terminado un `api_*.php`:

- [ ] ¿Tiene `ob_start()` y `ob_clean()`?
- [ ] ¿Valida sesión (`$_SESSION['user_id']`)?
- [ ] ¿Todos los SQL usan prepared statements con `?` o `:marcador`?
- [ ] ¿Tiene `header('Content-Type: application/json; charset=utf-8')`?
- [ ] ¿Usa `JSON_UNESCAPED_UNICODE` en `json_encode()`?
- [ ] ¿Valida roles antes de operaciones sensibles?
- [ ] ¿Tiene try/catch alrededor de la lógica?
- [ ] ¿Las transacciones tienen `beginTransaction()` y `commit()`/`rollBack()`?
- [ ] ¿Responde SOLO JSON (sin HTML ni espacios en blanco)?
- [ ] ¿Documenta la estructura de input/output en comentario?

---

## 12. Debugging en Hostinger

Si obtienes "Unexpected end of JSON input":

```php
// Opción A: Verificar output buffering
echo "DEBUG_BUFFER_SIZE=" . ob_get_length() . "\n";  // MALO: agrega output
ob_clean();

// Opción B: Log en archivo (mejor)
error_log("DEBUG: respuesta lista", 0);
error_log(json_encode($datos), 0);

// Opción C: Response headers
header('X-Debug-Info: ' . date('Y-m-d H:i:s'));
```

---

## 13. Casos de Uso Comunes

### A. Buscar clientes con filtros complejos
```php
$where = "1=1";
if (!empty($q)) {
    $where .= " AND (c.razon_social LIKE :q OR c.legajo LIKE :q)";
    $params[':q'] = "%$q%";
}
if (!empty($estado)) {
    $where .= " AND gest.estado = :estado";
    $params[':estado'] = $estado;
}

$sql = "SELECT c.*, ... FROM clientes c LEFT JOIN ... WHERE $where";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
```

### B. Insertar con validación
```php
$legajo = trim($_POST['legajo'] ?? '');
if (empty($legajo)) {
    throw new Exception("El legajo es requerido.");
}

$stmt = $pdo->prepare("INSERT INTO clientes (legajo, razon_social) VALUES (?, ?)");
$stmt->execute([$legajo, $razon_social]);

echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
```

### C. Acción masiva con transacción
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
    echo json_encode(['success' => true, 'count' => count($legajos)]);
} catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
}
```

---

## 14. Referencias Rápidas

| Problema | Solución |
|----------|----------|
| "Unexpected end of JSON input" | `ob_clean()` después de `require` |
| Caracteres con tilde aparecen mal | Verificar `charset=utf8mb4` en PDO y `JSON_UNESCAPED_UNICODE` |
| Operador ve datos de otro operador | Validar `WHERE usuario_id = :uid` en queries |
| CSV importa pero BD no se actualiza | Verificar `$pdo->commit()` en transacción |
| Endpoint devuelve HTML en lugar de JSON | Hay output antes del JSON (espacios tras `?>`) |
| Hora de `fecha_gestion` está desfasada | Falta `SET time_zone = '-03:00'` en db.php |

---

## 15. Patrón Completo (Ejemplo Real)

Ver `api_gestion.php` del CRM.ROQUE como referencia:
- Estructura: `ob_start()` → `require` → `ob_clean()` → headers
- Seguridad: Validación de roles antes de cada operación
- SQL: Prepared statements obligatorios
- JSON: Respuestas limpias con `JSON_UNESCAPED_UNICODE`
- Transacciones: `beginTransaction()` / `commit()` / `rollBack()`

Copia este patrón para cualquier nuevo endpoint.

