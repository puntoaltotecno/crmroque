# CRM.ROQUE — Sistema de Gestión de Cobranzas

Sistema web de seguimiento de cartera de clientes con asignación de operadores, historial de gestiones, panel de estadísticas (Tablero) y control de accesos.

---

## Tecnologías

- **Backend:** PHP 7.4+ con PDO (Sin frameworks, prepared statements obligatorios)
- **Base de datos:** MySQL / MariaDB
- **Frontend:** SPA con Vanilla JavaScript (Fetch API) + Tailwind CSS CDN
- **Servidor local:** XAMPP (Apache + MySQL)
- **Servidor producción:** Hostinger

---

## Estructura de archivos

```text
/
├── index.php                     → App principal (login, cartera activa, equipo y tablero)
├── login.php                     → Autenticación de usuarios
├── db.php                        → Conexión PDO (detecta local vs producción automáticamente)
├── reset.php                     → Herramienta de emergencia: resetea password del admin a '123456'
├── api_clientes.php              → Lista, busca, filtra clientes y calcula estadísticas
├── api_gestion.php               → Guarda gestiones en el historial (con validación de roles)
├── api_historial.php             → Devuelve la línea de tiempo de un cliente por legajo
├── api_usuarios.php              → CRUD de operadores (solo Admin puede crear/editar/eliminar)
├── api_asignar.php               → Asignación manual de un legajo desde la ficha 360
├── api_masivo.php                → Acciones masivas: asignar operador, cambiar estado, eliminar
├── api_importar_csv.php          → Importación masiva de clientes desde CSV (con reglas de reingreso)
├── api_importar_asignaciones.php → Importación masiva de asignaciones desde CSV (email + legajo)
└── api_dashboard.php             → Métricas globales, estados de cartera y rendimiento por operador
```

---

## Roles y Permisos

El sistema maneja 3 niveles de acceso estandarizados:

### Admin (Administrador Total)
- Acceso total al sistema.
- Acceso al Tablero (Dashboard) con métricas financieras y de rendimiento operativo.
- Puede importar clientes y asignaciones vía CSV.
- Puede gestionar el equipo de usuarios (crear, editar, eliminar).
- Acceso a acciones masivas, incluyendo eliminación de legajos.
- Único rol que puede clasificar clientes como **"Al Día"** desde acciones masivas.

### Colaborador (Coordinador)
- Acceso al Tablero (Dashboard) para monitorear el rendimiento del equipo.
- Puede asignar carteras y ver a todo el personal.
- Puede usar acciones masivas (excepto eliminación).
- No puede importar CSV ni eliminar usuarios/legajos.

### Operador (Base)
- Solo visualiza la cartera general; las métricas financieras globales están ocultas.
- No tiene acceso a la pestaña del Tablero de métricas.
- **Restricción de Seguridad #1:** No puede clasificar un cliente como **"Al Día"**. El intento es bloqueado en `api_gestion.php` con error explícito.
- **Restricción de Seguridad #2:** Si un cliente ya tiene estado "Al Día", el formulario de gestión se bloquea completamente para el operador.

---

## Reglas de Negocio y Automatizaciones

**Semáforo de vencimiento de promesas:**
- 🔴 Rojo → Estado `promesa` y `fecha_promesa < CURDATE()`
- 🟡 Amarillo → Estado `promesa` con fecha futura
- ⚪ Blanco → Sin gestión (`estado IS NULL`)
- 🟢 Verde → Cualquier otro estado gestionado

**Tablero de Rendimiento:** Calcula en tiempo real la efectividad de los operadores (`promesas_logradas / clientes_gestionados`), con semáforos de color: Verde >30%, Naranja >15%, Rojo <15%.

**Lógica de Importación CSV — Reingresos:** Si un cliente estaba marcado como "Al Día" pero vuelve a aparecer en un nuevo CSV importado, el sistema asume que volvió a tener deuda y le inserta automáticamente un registro con estado `NULL` (Pendiente / Sin gestión).

**Lógica de Importación CSV — Salidas:** Si un cliente que estaba en la base de datos ya no figura en el CSV importado, el sistema lo pasa automáticamente al estado "Al Día" (gestión automática del sistema).

**Importación de Asignaciones CSV:** El archivo debe tener dos columnas: `email` (del operador) y `legajo`. Si el email existe en el sistema y el legajo está en la base, se asigna/reasigna automáticamente. Las filas con email no reconocido se cuentan como ignoradas.

---

## Base de Datos

### Tablas principales

#### `clientes`
Datos fijos del cliente importados desde el sistema de origen.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | INT AUTO_INCREMENT | PK |
| `l_entidad_id` | INT UNIQUE | ID externo del sistema origen |
| `legajo` | VARCHAR(50) | **Clave de negocio principal** |
| `razon_social` | VARCHAR(150) | Nombre del deudor |
| `nro_documento` | VARCHAR(20) | DNI / CUIT |
| `total_vencido` | DECIMAL(15,2) | Monto adeudado |
| `mora` | DECIMAL(15,2) | Monto de mora |
| `dias_atraso` | INT | Días en mora |
| `ultimo_pago` | DATE | Fecha del último pago registrado |
| `vencimiento` | DATE | Fecha de vencimiento de la deuda |
| `c_cuotas` | INT | Cantidad de cuotas |
| `sucursal` | VARCHAR(100) | Sucursal de origen |
| `domicilio` | VARCHAR(200) | Domicilio del deudor |
| `telefonos` | VARCHAR(255) | Teléfonos de contacto |
| `email` | VARCHAR(100) | Email del deudor |
| `created_at` | TIMESTAMP | Fecha de alta en el sistema |

#### `gestiones_historial`
Línea de tiempo de cada cliente. **La gestión con el `id` más alto determina el "estado actual" del cliente.**

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | INT AUTO_INCREMENT | PK |
| `legajo` | VARCHAR(50) | FK lógica a `clientes.legajo` |
| `cliente_id` | INT | FK legacy (no usar para filtrar, usar `legajo`) |
| `usuario_id` | INT | FK a `usuarios.id` (quién hizo la gestión) |
| `estado` | ENUM | Ver valores válidos abajo |
| `fecha_promesa` | DATE | Solo aplica si `estado = 'promesa'` |
| `monto_promesa` | DECIMAL(10,2) | Solo aplica si `estado = 'promesa'` |
| `observaciones` | TEXT | Notas del operador |
| `fecha_gestion` | TIMESTAMP | Timestamp automático al insertar |

**Valores válidos del ENUM `estado`:**
`'promesa'`, `'no_responde'`, `'no_corresponde'`, `'llamar'`, `'numero_baja'`, `'otro'`, `'al_dia'`, `'carta'`

> ⚠️ El estado `NULL` o la ausencia de registros en esta tabla se interpreta en el frontend como **"Sin gestión / Pendiente"**.

#### `asignaciones`
Relación 1 a 1 entre legajo y operador actual. Si el legajo ya está asignado, `ON DUPLICATE KEY UPDATE` reasigna al nuevo operador.

| Campo | Tipo | Descripción |
|---|---|---|
| `legajo` | VARCHAR(50) | PK + FK lógica a `clientes.legajo` |
| `usuario_id` | INT | FK a `usuarios.id` (con CASCADE DELETE) |

#### `usuarios`
Personal del sistema.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | INT AUTO_INCREMENT | PK |
| `nombre` | VARCHAR(100) | Nombre para mostrar en UI |
| `email` | VARCHAR(100) UNIQUE | Credencial de login |
| `password` | VARCHAR(255) | Hash bcrypt (`password_hash`) |
| `rol` | ENUM | `'admin'`, `'operador'`, `'colaborador'` |
| `activo` | TINYINT(1) | `1` = Activo, `0` = Bloqueado |
| `created_at` | TIMESTAMP | Fecha de creación |

---

## Script de Migración SQL

El estado `'carta'` fue agregado al ENUM de `gestiones_historial`. Si se trabaja sobre una base de datos existente, ejecutar:

```sql
ALTER TABLE `gestiones_historial`
  MODIFY COLUMN `estado` ENUM(
    'promesa','no_responde','no_corresponde','llamar',
    'numero_baja','otro','al_dia','carta'
  ) COLLATE utf8mb4_unicode_ci DEFAULT 'promesa';
```

---

## Historial de Cambios

### v1.3 — (2026-03-23)
- ✅ **Nuevo estado `carta`** agregado al ENUM de `gestiones_historial`. Disponible en el formulario de gestión y en acciones masivas. Requiere `ALTER TABLE` en bases existentes (ver script de migración arriba).
- 🔒 **Restricción de operadores reforzada:** `api_gestion.php` bloquea explícitamente que un operador envíe `estado = 'al_dia'`. Si el estado actual del cliente ya es `'al_dia'`, el sistema también bloquea cualquier edición por parte del operador.
- 🔄 **Lógica de reingreso CSV mejorada:** `api_importar_csv.php` detecta si un cliente que regresa al CSV estaba previamente en estado `'al_dia'` y le inserta automáticamente un registro `NULL` (Pendiente), reiniciando su ciclo de gestión.

### v1.2 — (anterior)
- ✅ Agregado `api_masivo.php` para acciones masivas (asignar operador, cambiar estado, eliminar).
- ✅ Agregado `api_dashboard.php` con métricas globales y rendimiento por operador.
- ✅ Agregado `api_importar_asignaciones.php` para importar asignaciones por CSV (email + legajo).
- ✅ Lógica de importación CSV con reglas de salida/reingreso automático.

### v1.1 — (anterior)
- 🐛 **Bugfix Login:** El formulario enviaba como GET en lugar de POST. Solución: `onsubmit="handleLogin(event)"` directo en el `<form>`, con la función `handleLogin()` en un `<script>` separado al final del `</body>`, siempre disponible independientemente del estado de sesión.

---

## Notas Importantes

- **`legajo` es la clave de negocio universal** que vincula todas las tablas. Nunca usar `id` para relacionar datos entre tablas.
- El campo `id` de `clientes` puede ser `0` en registros importados antes de agregar `AUTO_INCREMENT`. Toda la lógica crítica usa `legajo`.
- Las contraseñas se almacenan con `password_hash()` de PHP (bcrypt). Nunca en texto plano.
- El campo `activo` de `usuarios` actualmente **no** está validado en `login.php`. Un usuario con `activo = 0` puede seguir ingresando. Para bloquear cuentas, agregar `AND activo = 1` al query de autenticación.
- `reset.php` es una herramienta de emergencia que debe **eliminarse del servidor de producción** una vez utilizada.
