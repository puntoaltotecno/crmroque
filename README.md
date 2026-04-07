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

| Campo           | Tipo               | Descripción                      |
| --------------- | ------------------ | -------------------------------- |
| `id`            | INT AUTO_INCREMENT | PK                               |
| `l_entidad_id`  | INT UNIQUE         | ID externo del sistema origen    |
| `legajo`        | VARCHAR(50)        | **Clave de negocio principal**   |
| `razon_social`  | VARCHAR(150)       | Nombre del deudor                |
| `nro_documento` | VARCHAR(20)        | DNI / CUIT                       |
| `total_vencido` | DECIMAL(15,2)      | Monto adeudado                   |
| `mora`          | DECIMAL(15,2)      | Monto de mora                    |
| `dias_atraso`   | INT                | Días en mora                     |
| `ultimo_pago`   | DATE               | Fecha del último pago registrado |
| `vencimiento`   | DATE               | Fecha de vencimiento de la deuda |
| `c_cuotas`      | INT                | Cantidad de cuotas               |
| `sucursal`      | VARCHAR(100)       | Sucursal de origen               |
| `domicilio`     | VARCHAR(200)       | Domicilio del deudor             |
| `telefonos`     | VARCHAR(255)       | Teléfonos de contacto            |
| `email`         | VARCHAR(100)       | Email del deudor                 |
| `created_at`    | TIMESTAMP          | Fecha de alta en el sistema      |

#### `gestiones_historial`

Línea de tiempo de cada cliente. **La gestión con el `id` más alto determina el "estado actual" del cliente.**

| Campo           | Tipo               | Descripción                                     |
| --------------- | ------------------ | ----------------------------------------------- |
| `id`            | INT AUTO_INCREMENT | PK                                              |
| `legajo`        | VARCHAR(50)        | FK lógica a `clientes.legajo`                   |
| `cliente_id`    | INT                | FK legacy (no usar para filtrar, usar `legajo`) |
| `usuario_id`    | INT                | FK a `usuarios.id` (quién hizo la gestión)      |
| `estado`        | ENUM               | Ver valores válidos abajo                       |
| `fecha_promesa` | DATE               | Solo aplica si `estado = 'promesa'`             |
| `monto_promesa` | DECIMAL(10,2)      | Solo aplica si `estado = 'promesa'`             |
| `observaciones` | TEXT               | Notas del operador                              |
| `fecha_gestion` | TIMESTAMP          | Timestamp automático al insertar                |

**Valores válidos del ENUM `estado`:**
`'promesa'`, `'no_responde'`, `'no_corresponde'`, `'llamar'`, `'numero_baja'`, `'otro'`, `'al_dia'`, `'carta'`

> ⚠️ El estado `NULL` o la ausencia de registros en esta tabla se interpreta en el frontend como **"Sin gestión / Pendiente"**.

#### `asignaciones`

Relación 1 a 1 entre legajo y operador actual. Si el legajo ya está asignado, `ON DUPLICATE KEY UPDATE` reasigna al nuevo operador.

| Campo        | Tipo        | Descripción                             |
| ------------ | ----------- | --------------------------------------- |
| `legajo`     | VARCHAR(50) | PK + FK lógica a `clientes.legajo`      |
| `usuario_id` | INT         | FK a `usuarios.id` (con CASCADE DELETE) |

#### `usuarios`

Personal del sistema.

| Campo        | Tipo                | Descripción                              |
| ------------ | ------------------- | ---------------------------------------- |
| `id`         | INT AUTO_INCREMENT  | PK                                       |
| `nombre`     | VARCHAR(100)        | Nombre para mostrar en UI                |
| `email`      | VARCHAR(100) UNIQUE | Credencial de login                      |
| `password`   | VARCHAR(255)        | Hash bcrypt (`password_hash`)            |
| `rol`        | ENUM                | `'admin'`, `'operador'`, `'colaborador'` |
| `activo`     | TINYINT(1)`         | `1` = Activo, `0` = Bloqueado            |
| `created_at` | TIMESTAMP           | Fecha de creación                        |

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

1.7
Fecha: 6 de Abril, 20261. Sistema de Ordenamiento y VisualizaciónCorrección de Orden por Mora: Se implementó CAST(dias_atraso AS SIGNED) en api_clientes.php para asegurar que el ordenamiento numérico sea exacto (evitando que "100" aparezca antes que "20").Badge de Estado: Se agregaron estilos visuales (Tailwind) para los nuevos estados como "Al Día" y "Carta".2. Gestión de Clientes "Al Día"Automatización vía CSV: El importador (api_importar_csv.php) ahora detecta clientes que ya no vienen en el archivo. Si un cliente desaparece del CSV, el sistema asume deuda $0 y cambia su estado automáticamente a "Al Día" con una gestión de sistema.Restricciones de Seguridad: Los Operadores tienen bloqueada la posibilidad de cambiar el estado a "Al Día" manualmente. Solo Administradores y Colaboradores pueden hacerlo.Bloqueo de Edición: Una vez que un cliente está "Al Día", el formulario de gestión se bloquea para Operadores para evitar registros accidentales en carteras cerradas.3. Auditoría y Versiones (Punto 4)Edición de Gestiones: Se habilitó la edición de la última gestión para operadores (máximo 3 intentos) y edición ilimitada para administradores.Eliminación Lógica: Implementación de la columna oculta en la base de datos. Las gestiones eliminadas no desaparecen de la BD, solo se ocultan del timeline principal.Modo "Ver Ocultas": Botón exclusivo para Administradores en el modal de cliente para auditar registros eliminados.4. Comunicados Flotantes (Punto 6)Mensajería Interna: Creación de api_comunicados.php y la tabla comunicados.Segmentación: Capacidad de enviar avisos "Para Todos" o mensajes privados a un operador específico.Interfaz: Banner flotante oscuro con animación de entrada y memoria local (localStorage) para que el aviso no reaparezca una vez cerrado por el usuario.5. Estabilidad en Hostinger (Producción)Optimización de JSON: Se implementó limpieza de buffer (ob_clean) y forzado de UTF-8 en api_dashboard.php para evitar el error "Unexpected end of JSON input".Ajuste de .htaccess: Se eliminó la restricción que bloqueaba archivos con contenido JSON para permitir la comunicación fluida entre el frontend y las APIs.Detección de Entorno: El archivo db.php ahora detecta automáticamente si está en localhost o en el servidor de Hostinger.Script de Reparación: Creación de reparar.php para sincronizar la estructura de tablas entre el ambiente de desarrollo y el servidor web.Estado Actual: 100% Funcional en Hostinger.

### v1.6 — (2026-03-30)
### 🌍 Búsqueda Global y Gestión Colaborativa
* **Buscador Universal:** Al utilizar el campo de búsqueda (por Legajo, DNI o Razón Social), el sistema ahora rastrea en toda la base de datos, omitiendo temporalmente el filtro de cartera asignada del operador. Si el buscador está vacío, el operador vuelve automáticamente a su vista de clientes asignados.
* **Trabajo Colaborativo Blindado:** Un operador puede atender y registrar gestiones para un cliente que pertenece a otro compañero. Esta gestión quedará firmada en el historial por el operador que la realizó (asegurando la trazabilidad), pero **la asignación original del cliente no se modifica**.
* **Alertas Visuales de Propiedad:** Al abrir la ficha de un cliente desde el buscador, el sistema detecta a quién pertenece. Si el cliente es de otro operador (o no está asignado), se despliega una advertencia visual amarilla indicando: *"⚠️ Asignado a [Nombre] - Tu gestión quedará a tu nombre"*, previniendo confusiones operativas.

### v1.5 — (2026-03-24)

- 🕐 **Fix zona horaria:** `db.php` ahora establece `America/Argentina/Buenos_Aires` para PHP y `SET time_zone = '-03:00'` para MySQL en cada conexión. Corrige el desfase de 3 horas (UTC vs UTC-3) que afectaba el `fecha_gestion` grabado en `gestiones_historial`. Los registros anteriores a este fix conservan la hora incorrecta; los nuevos se graban con hora local correcta.

### v1.4 — (2026-03-24)

- 👁️ **Visibilidad de Vencimiento:** Se habilitó la visualización de la columna "Vencimiento" para los usuarios con rol `operador`, permitiendo una mejor referencia temporal durante la gestión de cobranza.
- 📊 **Ordenamiento por Atraso:** Se modificó la lógica en el frontend (`index.php`) para que los resultados se ordenen automáticamente de menor a mayor según los días de atraso, priorizando la gestión de deuda temprana directamente en la visualización del usuario.

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

## 🚀 Últimas Actualizaciones (Marzo 2026)

### 🔔 Nuevo Sistema de Notificaciones (Alertas de Agenda)
* **Campana de Alertas Inteligente:** Se agregó un ícono de notificaciones en la barra superior con un contador en tiempo real.
* **Control de Vencimientos:** El sistema detecta automáticamente cualquier gestión (Promesa, Llamar luego, Carta, etc.) cuya fecha agendada sea **igual o anterior al día de hoy**.
* **Filtro por Roles:** Los *Operadores* solo reciben alertas de su propia cartera de clientes, mientras que los *Administradores* y *Colaboradores* tienen una vista global de todas las alertas del sistema.
* **Acceso Rápido:** Al hacer clic en una alerta del menú desplegable, se abre instantáneamente el modal del cliente para gestionarlo sin tener que buscarlo en la tabla.

### 🔄 Sincronización Inteligente (Importación de CSV)
* **Motor de Importación Reescrito:** El archivo `api_importar_csv.php` ahora procesa los datos fila por fila consultando directamente a la base de datos, eliminando errores de actualización.
* **Limpieza Automática (Deuda $0):** Si un cliente que estaba en la base de datos ya no figura en el nuevo archivo CSV (porque regularizó su deuda), el sistema automáticamente pone sus cuotas, días de atraso y monto vencido en `0`, y cambia su estado a `Al Día`.
* **Reingreso de Clientes:** Si un cliente estaba "Al Día" pero vuelve a aparecer en el reporte de mora (CSV), el sistema le quita el estado "Al Día" y lo vuelve a poner como pendiente de gestión.
* **Robustez de Formatos:** Detección automática de delimitadores (comas o punto y coma), limpieza de caracteres invisibles (BOM) y corrección automática de fechas que vengan con o sin hora (ej: `2025-11-20 09:26:55` se procesa como `2025-11-20`).

### 📊 Mejoras en la Tabla Principal y UI
* **Visibilidad de Montos:** Se liberó la restricción visual para los Operadores; ahora pueden ver los montos vencidos y los días de atraso directamente en la tabla y en el panel del cliente.
* **Ordenamiento Numérico Real:** Se corrigió el listado general (`api_clientes.php`) usando `CAST(dias_atraso AS SIGNED)`. Ahora la tabla ordena a los clientes estrictamente de menor a mayor cantidad de días de atraso, evitando fallos de ordenamiento alfabético (ej. que 100 aparezca antes que 2).

### ⚙️ Servidor y Base de Datos
* **Sincronización de Zona Horaria:** Se configuró `db.php` para forzar la zona horaria `America/Argentina/Buenos_Aires` y el offset `-03:00` en MySQL. Esto garantiza que las fechas y horas de las gestiones guarden el momento exacto de Argentina, mitigando desfases al alojar el sistema en servidores extranjeros (como Hostinger).

- **`legajo` es la clave de negocio universal** que vincula todas las tablas. Nunca usar `id` para relacionar datos entre tablas.
- El campo `id` de `clientes` puede ser `0` en registros importados antes de agregar `AUTO_INCREMENT`. Toda la lógica crítica usa `legajo`.
- Las contraseñas se almacenan con `password_hash()` de PHP (bcrypt). Nunca en texto plano.
- El campo `activo` de `usuarios` actualmente **no** está validado en `login.php`. Un usuario con `activo = 0` puede seguir ingresando. Para bloquear cuentas, agregar `AND activo = 1` al query de autenticación.
- `reset.php` es una herramienta de emergencia que debe **eliminarse del servidor de producción** una vez utilizada.
- Los registros de `gestiones_historial` grabados **antes de la v1.5** tienen hora en UTC (3 horas adelantada respecto a Argentina). A partir de la v1.5 se graban en hora local correcta.
