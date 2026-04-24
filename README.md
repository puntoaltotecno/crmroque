# CRM.ROQUE — Sistema de Gestión de Cobranzas

Sistema web de seguimiento de cartera de clientes con asignación de operadores, historial de gestiones, panel de estadísticas (Tablero), control de accesos y filtros avanzados.

---

## 🛠️ Tecnologías
* **Backend:** PHP 7.4+ con PDO (Sin frameworks, prepared statements obligatorios).
* **Base de datos:** MySQL / MariaDB.
* **Frontend:** SPA con Vanilla JavaScript (Fetch API) + Tailwind CSS v2 local (archivo estático `tailwind.min.css`).
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

### v3.0 — Sistema de Filtrado por Sucursal y Navegación (24 de Abril, 2026)

* 🏢 **Filtrado Global por Sucursal:** Se implementó un selector de sucursal en la barra de herramientas que afecta tanto al Tablero (Dashboard) como al listado de clientes, permitiendo análisis específicos por boca de cobranza.
* ↔️ **Navegación en Modal:** Se añadieron botones "Anterior" y "Siguiente" dentro de la ficha del cliente. Esto permite a los operadores recorrer su lista de trabajo sin necesidad de cerrar y abrir el modal para cada cliente.
* 📊 **Conexión Directa Tablero-Lista:** Al hacer clic en las métricas de "Gestiones" del Tablero, el sistema redirige automáticamente al listado de clientes aplicando los filtros correspondientes (Sucursal + Tipo de Resultado).

### v2.9.2 — Precisión en Métricas y UI de Rendimiento (23 de Abril, 2026)

* 🎯 **Métricas por Clientes Únicos:** Se corrigió el cálculo en "Métricas por Sucursal". Ahora el sistema cuenta legajos únicos gestionados en lugar de interacciones totales, brindando una visión real de la cobertura de cartera.
* 📈 **Nuevas Columnas de Rendimiento:** Se incorporó el cálculo de "% Gestionados" en las tablas de estadísticas, con semáforos de color para identificar rápidamente el cumplimiento de objetivos.
* 📱 **Optimización de Scroll:** Se eliminaron desbordamientos visuales en las tablas de reportes, asegurando una experiencia fluida en dispositivos móviles.

### v2.9.1 — Historial de Deuda y Analítica Avanzada (22 de Abril, 2026)

* 📈 **Evolución de Deuda:** Implementación de la tabla `historial_deuda` y motor de snapshots automáticos durante la importación CSV. Permite visualizar la evolución del monto vencido día por día.
* 🎯 **Métricas de Operador Corregidas:** Se optimizó la atribución del estado "Al Día" en los reportes de ranking y efectividad. Ahora el sistema reconoce los éxitos basándose en la asignación del cliente, permitiendo que el trabajo de los operadores se refleje correctamente aunque el cambio de estado lo realice un administrador.
* 🔍 **Filtros Globales en Reportes:** Se añadieron selectores de Operador, buscador por Cliente y límite de resultados (Top 25/50/All) para mejorar el rendimiento de carga.
* 📥 **Exportación Completa:** Se independizó la exportación (CSV/JSON) de los límites visuales de la interfaz, permitiendo descargar el conjunto total de datos filtrados.

### v2.9 — Optimización UI/UX del Panel de Acciones Masivas (22 de Abril, 2026)

* 🛠️ **Acciones Masivas Responsivas:** Se rediseñó el panel `#bulk-actions` para ser completamente flexible (`flex-wrap`). Los controles se agruparon en cápsulas visuales, eliminando la barra de desplazamiento horizontal y mejorando el flujo en dispositivos móviles y tablets.
* 👁️ **Contraste de Selectores:** Se corrigió un problema de legibilidad en los menús desplegables sobre fondos oscuros. Ahora las opciones mantienen un contraste alto (texto oscuro sobre fondo blanco) en todos los navegadores y sistemas operativos.
* 📏 **Diseño Compacto:** Se optimizó el acolchado y espaciado del panel inferior para maximizar el área de trabajo útil de la tabla de clientes.

### v2.8 — Desactivación Lógica de Operadores (Soft Delete) (22 de Abril, 2026)

* 🔒 **Bloqueo/Deshabilitación (Soft Delete):** Se reemplazó la eliminación física de operadores por una desactivación lógica (`activo = 0`). Esto preserva intactas todas las relaciones de base de datos (asignaciones e historial de gestiones) previniendo que los clientes queden huérfanos y garantizando la coherencia de las métricas.
* 👥 **Gestión de Personal:** Los administradores ahora ven a los operadores desactivados con una etiqueta "Inactivo" en la lista de gestión. Un operador inactivo no puede iniciar sesión ni ser asignado a nuevos clientes (se excluye de los menús desplegables), pero su historial es completamente trazable.
* 🔄 **Reactivación Inmediata:** Los operadores desactivados pueden ser reactivados en cualquier momento mediante el nuevo botón dinámico Activar/Desactivar.

### v2.7 — Unificación WhatsApp, Consolidación de Tabla y Flujo Manual (21 de Abril, 2026)

* 📱 **WhatsApp Inteligente (`redirigirWA`):** Se eliminó la doble opción Web/App en los filtros. Ahora existe un único toggle **"WhatsApp"**. Al usarlo en PC, el sistema detecta y consulta al operador si desea usar la versión Web o Desktop, centralizando la lógica y reduciendo clics innecesarios.
* 📋 **Consolidación de Tabla de Clientes:** Se fusionaron las columnas dispersas en una sola celda maestra **"Cliente"** que agrupa: Legajo, Documento, Razón Social y Sucursal. Esto resolvió el desalineamiento estructural de los headers y mejoró la lectura en móviles.
* 🛑 **Fin del Avance Automático:** A pedido de los operadores, se desactivó el salto automático al siguiente cliente tras guardar. Ahora el modal permanece en el cliente actual, actualiza su historial instantáneamente y limpia el formulario, permitiendo revisar la carga antes de continuar manualmente.
* 🐛 **Fix de Desbordamiento y Doble Scroll:** Se eliminaron contenedores anidados con `overflow-x-auto` que causaban que el botón "Gestionar" se cortara en ciertas resoluciones. La tabla ahora utiliza un solo contenedor de desplazamiento suave (`custom-scroll`).
* 🧹 **Limpieza UI de Contactos:** Se redujeron los canales visibles a lo esencial: WhatsApp, Llamar y SMS, despejando la barra de filtros superior.

### v2.6 — Refinando Estética y Memoria (21 de Abril, 2026)

### v2.4 — Optimización de Hostinger, UX Móvil Completa y UI Fixes (18 de Abril, 2026)

* 🚀 **Migración a Tailwind CSS Local:** Se discontinuó el uso de Tailwind V3 JIT CDN debido a estrictas políticas de seguridad CSP en resoluciones de Hostinger. Ahora la plataforma opera íntegramente sobre un archivo minificado estático local v2, garantizando su carga independientemente del entorno.
* 🧹 **Eliminación del Menú Inferior (Bottom Bar):** Se removió de forma permanente el menú de navegación inferior en celulares a pedido de los operadores, delegando toda acción principal en el menú lateral de origen (hamburguesa) para maximizar el área de trabajo vertical visible.
* 📱 **Tablas con Scroll Horizontal:** Se quitó la responsabilidad de ocultamiento y colapso extremo de las columnas en las tablas para las pantallas pequeñas. Ahora, las tablas mantienen **todas las columnas de información originales** intactas con desplazamiento horizontal transversal, evitando cruces de datos por adaptabilidad.
* 🎨 **Resolución de Colores Nativos (V2):** Corregido un gran número de visualizaciones desajustadas relacionadas a colores extendidos excluidos en CSS estáticos v2 (como "emerald" y "teal"), los cuales fueron migrados a la paleta clásica "green". Del mismo modo se definieron reglas inline para "Guardar Gestión" habilitando el `.bg-crm-blue` en duro.
* 🔎 **Ajustes de Cartera Activa:** Se relocalizó la caja del buscador de red por sobre las líneas de filtros para evitar el estrangulamiento de los campos select.

### v2.3 — Optimización de UX Móvil y Navegación SPA (16 de Abril, 2026)

* 📱 **Navegación Híbrida Móvil:** Implementación de un sistema dual para tablets y celulares que incluía un menú lateral y herramientas rápidas (Nota: La barra inferior se removió en v2.4).
* 📋 **Vista de Lista Optimizada:** Apertura de modales con un solo clic en la mesa de resultados de búsqueda táctil agilizando la gestión.
* 🗂️ **Modal de Gestión con Tabs:** Rediseño total del modal de cliente para pantallas pequeñas:
  - **Cabecera Persistente:** Datos del cliente e indicadores críticos siempre fijos en el top visual.
  - **Sistema de Tabs:** Control deslizante de pestañas limitando el historial y el control de gestiones para abolición de scrolls inifitos.
  - En desktop, se mantiene el diseño de doble columna original (side-by-side).

### v2.2 — Búsqueda por Teléfono e Identificación de Coincidencias (15 de Abril, 2026)

* 🔍 **Búsqueda por Teléfono:** Se habilitó la búsqueda de clientes por número de teléfono en la barra superior. El sistema normaliza automáticamente tanto la entrada del usuario como los datos en BD, permitiendo encontrar números sin importar si tienen espacios, guiones, paréntesis o signo +.
* 🏷️ **Etiquetas de Coincidencia (Match Tagging):** Cuando se realiza una búsqueda, el sistema ahora muestra una pequeña etiqueta descriptiva junto al legajo (ej: `🔍 TELÉFONO`, `🔍 DOCUMENTO`, `🔍 NOMBRE`) para que el operador identifique instantáneamente por qué campo se ha encontrado ese resultado.
* 🛠️ **Consistencia en Exportación:** La lógica de búsqueda por teléfono se replicó en el motor de exportación `exportar_csv.php`, asegurando que el reporte generado coincida con lo que el operador ve en pantalla.
* 🐛 **Fix UI Search:** Se corrigió un error de sintaxis HTML en el input de búsqueda que tenía el atributo `id` duplicado.

### v2.1 — Comunicación Multi-Canal y Optimización WhatsApp (13 de Abril, 2026)

* ⏰ **Recordatorio de Promesas Inteligente:** Dentro del Historial de Gestiones, si una gestión es "Promesa", se agregó un botón **"💬 Recordatorio"** junto a la fecha pactada. Al pulsarlo:
  - Si el cliente tiene un solo número, inicia automáticamente el chat con el recordatorio.
  - Si tiene múltiples y/o se ingresa uno distinto mediante un control de alerta, el sistema adaptará a cuál enviar automáticamente.
  - Respeta dinámicamente si el operador utiliza la versión Web (para PC), su App movil o la de Escritorio, aplicando prefijos argentinos integrados (+549).
* 🎛️ **Filtros de Contacto por Operador:** Se agregaron _toggles_ (interruptores) en la barra de búsqueda que permiten a cada operador elegir qué botones de contacto ver por defecto. Esto ayuda a limpiar la interfaz ocultando botones que no se usan con frecuencia. La configuración se guarda en el navegador de cada usuario (`localStorage`), y por defecto solo muestra **WhatsApp Web**.
* 📞 **Botón Llamar (tel:):** Se añadió un botón azul **"📞 LLAMAR"** por cada número del cliente. Al hacer clic, inicia una llamada directa usando la línea telefónica del dispositivo del operador (celular nativo o apps de PC como Enlace Telefónico de Windows).
* ✉️ **Botón SMS (sms:):** Se añadió un botón violeta **"✉️ SMS"** por cada número. Abre la aplicación de mensajes de texto con un mensaje predefinido que incluye el legajo del cliente para identificación.
* 🔄 **Pestaña Única WhatsApp Web:** Los enlaces de WhatsApp Web ahora reutilizan siempre la misma pestaña del navegador (`target='whatsapp_crm'`) en lugar de abrir una nueva pestaña por cada clic. Esto evita la acumulación de pestañas durante la jornada del operador.
* 🆔 **Legajo en Mensaje WhatsApp:** El mensaje predefinido que se envía por WhatsApp ahora incluye el **número de legajo** del cliente al final del texto (`_Op: LEGAJO_`). Esto permite identificar rápidamente al cliente cuando responde, sin necesidad de cruzar datos manualmente.

### v2.0 — WhatsApp, Validaciones y Métricas (11 de Abril, 2026)

* 📱 **Integración WhatsApp por Número:** El modal de gestión de clientes ahora muestra un botón 💬 verde por **cada número de teléfono** registrado. Al hacer clic se abre WhatsApp (Web en PC, App en celular) con un mensaje predefinido firmado con el nombre del operador logueado. Formatea automáticamente el número al estándar argentino `+549XXXXXXXXXX`.
* 🔒 **Validación de Fecha de Gestión:** Se corrigió un bug que permitía guardar fechas pasadas en los estados `Promesa de Pago` y `Llamar más tarde`. Ahora el campo de fecha tiene `min=hoy` en el frontend Y validación server-side en `api_gestion.php` para ambas acciones (insertar y editar). La fecha es siempre obligatoria para esos estados.
* 📊 **Gestiones por Sucursal:** Se añadió una nueva columna **"Gestiones"** en la tabla "Métricas por Sucursal" del Tablero, mostrando el total histórico de interacciones por cada sucursal (excluyendo gestiones eliminadas).
* 🎯 **Fix Efectividad Operadores (sin desbordamiento de 100%):** Se corrigió la fórmula de efectividad en `api_reportes.php` y en el Tablero. Ahora se calcula como `Promesas / Total Gestiones` en lugar de `Promesas / Clientes Únicos`, lo que evitaba que un solo cliente con múltiples promesas inflara el porcentaje por encima del 100%.
* 🐛 **Fix `gestionados is not defined`:** Se corrigió un error de JavaScript en el Tablero causado por el renombrado de variable durante el fix de efectividad. La celda de "Gestionados" ahora muestra correctamente el total de gestiones del operador.

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
* **Buscador Inteligente:** Permite rastrear clientes por Legajo, DNI, Razón Social, Sucursal y **Número de Teléfono**. La búsqueda por teléfono es insensible al formato (ignora `+`, `-`, `()`, espacios). El sistema devuelve una etiqueta de coindicencia (`match_reason`) para identificar el origen del acierto en la UI.
* **La clave de negocio universal es `legajo`**: NUNCA utilizar el ID de la base de datos para vincular historial de gestiones o llamadas a las APIs. Toda operación depende de `legajo`.
* El archivo `reset.php` es una herramienta de emergencia y debe eliminarse del servidor de producción tras su uso.
* Las contraseñas usan `password_hash()` (bcrypt).
* Si el sistema falla retornando JSON inválido, revisar espacios en blanco antes de `<?php` en los archivos API.
