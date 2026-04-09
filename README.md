# CRM.ROQUE — Sistema de Gestión de Cobranzas

Sistema web de seguimiento de cartera de clientes con asignación de operadores, historial de gestiones, panel de estadísticas (Tablero), control de accesos y filtros avanzados.

---

## 🛠️ Tecnologías
* **Backend:** PHP 7.4+ con PDO (Sin frameworks, prepared statements obligatorios).
* **Base de datos:** MySQL / MariaDB.
* **Frontend:** SPA con Vanilla JavaScript (Fetch API) + Tailwind CSS CDN.
* **Servidor local:** XAMPP (Apache + MySQL).
* **Servidor producción:** Hostinger.

---

## 📁 Estructura de archivos
```text
/
├── index.php                 → App principal (login, cartera activa, equipo y tablero)
├── login.php                 → Autenticación de usuarios
├── db.php                    → Conexión PDO (detecta local vs producción automáticamente)
├── reset.php                 → Herramienta de emergencia: resetea password del admin a '123456'
├── exportar_csv.php          → Descarga de Excel respetando filtros activos (incluye Moto)
├── api_clientes.php          → Lista, busca, filtra clientes y calcula estadísticas
├── api_gestion.php           → Guarda gestiones en el historial (con validación de roles)
├── api_historial.php         → Devuelve la línea de tiempo de un cliente por legajo
├── api_usuarios.php          → CRUD de operadores (solo Admin)
├── api_asignar.php           → Asignación manual de un legajo desde la ficha 360
├── api_masivo.php            → Acciones masivas: asignar operador, cambiar estado, eliminar
├── api_importar_csv.php      → Importación masiva de clientes desde CSV (con reglas de reingreso)
├── api_importar_asignaciones.php → Importación masiva de asignaciones (email + legajo)
├── api_dashboard.php         → Métricas globales, estados de cartera y rendimiento por operador
├── api_comunicados.php       → Motor de mensajería y alertas flotantes internas
└── api_mi_rendimiento.php    → Panel personal de operadores, ranking y agenda diaria
```

---

## 🔐 Roles y Permisos

**1. Admin (Administrador Total)**
* Acceso total al sistema y al Tablero (Dashboard).
* Puede importar clientes y asignaciones vía CSV.
* Puede gestionar el equipo de usuarios (crear, editar, eliminar).
* Acceso a acciones masivas, incluyendo eliminación de legajos.
* Único rol que puede clasificar clientes como "Al Día" desde acciones masivas.
* Puede ver y restaurar gestiones eliminadas.
* Visualiza la Agenda Global completa omitiendo filtros de asignación.

**2. Colaborador (Coordinador)**
* Acceso al Tablero (Dashboard) para monitorear el rendimiento del equipo.
* Puede asignar carteras y ver a todo el personal.
* Puede usar acciones masivas (excepto eliminación).
* No puede importar CSV ni eliminar usuarios/legajos.
* Visualiza la Agenda Global completa omitiendo filtros de asignación.

**3. Operador (Base)**
* Solo visualiza la cartera general; las métricas financieras globales están ocultas.
* No tiene acceso a la pestaña del Tablero de métricas.
* Restricción #1: No puede clasificar un cliente como "Al Día".
* Restricción #2: Si un cliente ya está "Al Día", el formulario de gestión se bloquea.
* Puede editar sus propias gestiones hasta 3 veces (si es la última del historial).
* Su tablero de "Mi Rendimiento" y "Agenda" es estrictamente personal.

---

## ⚙️ Reglas de Negocio y Automatizaciones

* **Semáforo de Vencimiento:**
  * 🔴 Rojo → Estado promesa y fecha_promesa < CURDATE()
  * 🟡 Amarillo → Estado promesa con fecha futura
  * ⚪ Blanco → Sin gestión (estado IS NULL)
  * 🟢 Verde → Cualquier otro estado gestionado
* **Tablero de Rendimiento:** Calcula la efectividad (`promesas_logradas / clientes_gestionados`). Semáforos: Verde >30%, Naranja >15%, Rojo <15%.
* **Importación CSV — Reingresos:** Si un cliente estaba "Al Día" pero vuelve a aparecer en el CSV, se le inserta un registro pendiente para reiniciar su gestión.
* **Importación CSV — Salidas:** Si un cliente en BD ya no figura en el CSV, el sistema asume deuda $0 y lo pasa automáticamente a "Al Día".
* **Buscador Universal Colaborativo:** Permite buscar y gestionar clientes asignados a otros operadores. Advierte con una alerta amarilla (⚠️) pero registra la gestión a nombre de quien la realiza, sin robar la asignación original.

---

## 🗄️ Base de Datos

### Tabla `clientes`
Datos fijos del cliente importados desde el sistema de origen. Se agregó soporte optimizado para cartera de motocicletas.

| Campo | Tipo | Descripción |
|---|---|---|
| id | INT AUTO_INCREMENT | PK |
| l_entidad_id | INT UNIQUE | ID externo del sistema origen |
| legajo | VARCHAR(50) | Clave de negocio principal |
| razon_social | VARCHAR(150) | Nombre del deudor |
| nro_documento | VARCHAR(20) | DNI / CUIT |
| total_vencido | DECIMAL(15,2) | Monto adeudado |
| mora | DECIMAL(15,2) | Monto de mora |
| dias_atraso | INT | Días en mora |
| ultimo_pago | DATE | Fecha del último pago registrado |
| vencimiento | DATE | Fecha de vencimiento de la deuda |
| c_cuotas | INT | Cantidad de cuotas |
| sucursal | VARCHAR(100) | Sucursal de origen |
| domicilio | VARCHAR(200) | Domicilio del deudor |
| telefonos | VARCHAR(255) | Teléfonos de contacto |
| email | VARCHAR(100) | Email del deudor |
| moto | TINYINT(1) | 1 = Deuda de Moto, 0 = Normal (Indexado) |
| created_at | TIMESTAMP | Fecha de alta en el sistema |

### Tabla `gestiones_historial`
Línea de tiempo. La gestión con el ID más alto determina el "estado actual".

| Campo | Tipo | Descripción |
|---|---|---|
| id | INT AUTO_INCREMENT | PK |
| legajo | VARCHAR(50) | FK lógica a clientes.legajo |
| usuario_id | INT | FK a usuarios.id |
| estado | ENUM | Valores: promesa, no_responde, no_corresponde, llamar, numero_baja, otro, al_dia, carta |
| fecha_promesa | DATE | Aplica si estado = promesa o llamar |
| monto_promesa | DECIMAL(10,2) | Aplica si estado = promesa |
| observaciones | TEXT | Notas del operador |
| oculta | TINYINT(1) | 1 = Eliminación lógica (solo Admin puede ver/restaurar) |
| fecha_gestion | TIMESTAMP | Timestamp automático local |

---

## 🚀 Historial de Cambios

### v1.9 — Módulo Mi Rendimiento y Agenda (9 de Abril, 2026)
* 📈 **Panel "Mi Rendimiento":** Se implementó un dashboard personal para operadores con una barra de progreso comparativa frente al líder mensual y un **Ranking de Operadores** (`api_mi_rendimiento.php`).
* 📅 **Mi Agenda Hoy:** Se integró un listado interactivo con los clientes vencidos y a llamar del día. Los administradores y colaboradores ven la agenda global; los operadores ven solo las gestiones asignadas a ellos.
* 💡 **Guía de Respuestas (Tips):** Se añadió un sistema dinámico en el modal de gestión (`index.php`) que ofrece consejos contextuales al operador según el estado seleccionado (ej. "No responde", "Promesa").
* 🎯 **Limpieza de Métricas:** Se actualizaron `api_mi_rendimiento.php` y `api_reportes.php` excluyendo los roles no operativos (Admin/Colaboradores) y al usuario "Remanente" (id 42) de los cálculos de efectividad para asegurar un podio puramente operativo.
* 🛠️ **Refactor de Notificaciones / Modal:** Se corrigió un error que escondía la agenda por fechas nulas y problemas de tipografía SQL. Ahora saltar desde una alerta o agenda a la ficha del cliente no recarga la página, usando `abrirClientePorLegajo()` como modal flotante inteligente.

### v1.8 — Módulo de Deuda "Moto" (8 de Abril, 2026)
* 🏍️ **Nuevo Filtro Rápido:** Agregado checkbox "Deuda Moto" en la barra de herramientas superior para aislar carteras específicas al instante.
* 🚨 **Alertas Visuales Integradas:** Se añadió una etiqueta roja parpadeante (🚨 MOTO) visible tanto en el listado general como dentro del modal de gestión (junto al Legajo) para prevenir errores de atención.
* 📥 **Importación/Exportación Completa:** El motor de CSV (`api_importar_csv.php`) ahora detecta y guarda la columna moto. La exportación a Excel respeta el filtro si está activo.
* ⚡ **Rendimiento:** Creación del índice SQL `idx_moto` para garantizar búsquedas en milisegundos, incluso con grandes volúmenes de datos.

### v1.7 — Estabilidad Hostinger y Auditoría (6 de Abril, 2026)
* **Hostinger 100% Funcional:** Forzado de UTF-8 y limpieza de buffer (`ob_clean`) para evitar el error "Unexpected end of JSON input". Ajustes en `.htaccess`.
* **Auditoría y Borrado Lógico:** Las gestiones "eliminadas" ahora se ocultan (no se borran de la BD). Los Administradores tienen un botón "🔍 Ver eliminadas" para auditar y restaurar.
* **Edición de Gestiones:** Operadores pueden editar su última gestión (máximo 3 veces). Administradores sin límite.
* **Comunicados Flotantes:** Nuevo sistema de mensajería interna (`api_comunicados.php`) con banners flotantes y Short Polling cada 30 segundos.

### v1.6 — Búsqueda Global y Colaboración (30 de Marzo, 2026)
* **Buscador Universal:** Rastrea en toda la base, ignorando el filtro del operador temporalmente.
* **Trabajo Colaborativo Blindado:** Se permite gestionar clientes de otros compañeros dejando la firma del autor, con alerta visual (⚠️ Asignado a otro operador).

### v1.5 y v1.4 — Ajustes de UI y Zona Horaria (24 de Marzo, 2026)
* **Fix Zona Horaria:** `db.php` fuerza `America/Argentina/Buenos_Aires` y `SET time_zone = '-03:00'`.
* **Visibilidad para Operadores:** Operadores ahora pueden ver la columna "Vencimiento", "Total Vencido" y "Días de Atraso" en las tablas.
* **Ordenamiento Exacto:** Resultados ordenados por `dias_atraso` numéricamente usando `CAST()`.

### v1.3 a v1.1 — Core y Reglas Base
* Nuevo estado `carta` agregado al ENUM.
* Acciones masivas habilitadas para Administradores y Colaboradores.
* Lógica de reingreso CSV: Deuda regularizada ($0) se pasa a "Al Día" automáticamente.
* Fix de Login (Form request).

---

## ⚠️ Notas para el Desarrollador
* **`legajo`** es la clave de negocio universal. Nunca usar `id` para relacionar clientes con historiales o asignaciones.
* El archivo `reset.php` es una herramienta de emergencia y debe eliminarse del servidor de producción tras su uso.
* Las contraseñas usan `password_hash()` (bcrypt).
* Si el sistema falla retornando JSON inválido, revisar espacios en blanco antes de `<?php` en los archivos API.
