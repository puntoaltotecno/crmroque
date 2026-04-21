# CRM.ROQUE - Contexto para Agentes de IA

Este archivo contiene el historial, la estructura y las directrices fundamentales del proyecto CRM.ROQUE para informar a futuros agentes de inteligencia artificial y asistentes de programación (como Cursor, Copilot o Gemini).

## Propósito del Proyecto
Sistema web de seguimiento de cartera de clientes enfocado en la gestión de cobranzas. Facilita la asignación de operadores, el registro de historiales, comunicación multicanal (WhatsApp/SMS/Llamadas) y cálculo empírico de métricas de rendimiento y efectividad.

## Stack Tecnológico 🛠️
- **Backend:** PHP nativo (sin frameworks). Uso estricto de PDO con **prepared statements** para prevenir inyecciones SQL.
- **Base de Datos:** MySQL / MariaDB.
- **Frontend:** SPA (Single Page Application) elaborada con Vanilla JavaScript (utilizando Fetch API) y **Tailwind CSS v2** (archivo local `tailwind.min.css` para evitar bloqueos de seguridad CSP en Hostinger).
- **Entorno:** XAMPP para el entorno local y Hostinger para producción.

## Estructura de Archivos 📁
El proyecto está construido de manera plana. Todos los archivos se encuentran en la raíz del repositorio.
- `index.php`: Interfaz SPA principal. Centraliza todas las capas front y expone el modal de gestión multicanal (`openModal`).
- `db.php`: Conector PDO universal. Ajusta dinámicamente credenciales (servidor local vs. Hostinger) y fuerza zona horaria `America/Argentina/Buenos_Aires`.
- Archivos API (`api_*.php`): Controladores backend que reciben llamadas AJAX/Fetch y devuelven **exclusivamente JSON**. Ej: `api_clientes.php`, `api_gestion.php`, `api_reportes.php`.
- Importación/Exportación: Scripts dedicados al procesamiento de archivos Excel como `api_importar_csv.php` (con lógicas para reinicios de deuda).

## Roles y Permisos Principales 🔐
1. **Admin:** Administrador total. Importa CSV, borra legajos manualmente, gestiona usuarios y tiene control total sobre los estados de gestión.
2. **Colaborador (Dashboard):** Visualiza rendimiento general y realiza asignaciones masivas. No tiene funciones destructivas.
3. **Operador:** Visualización bloqueada para métricas financieras globales. Solo ve su propia agenda. 
   - **Restricción de Seguridad v2.6:** El operador **NUNCA** puede categorizar clientes en estado "Al Día" por su cuenta (validado en backend y frontend). El sistema bloquea interacciones si el cliente está "Al Día".

## Reglas de Negocio Vitales (v2.6) ⚙️
- **Gestión de Estados:** Se ha incorporado el estado **'Carta'** como categoría oficial (badge fucsia).
- **Buscador Inteligente:** Rastreo por Legajo, DNI, Razón Social, Sucursal y **Número de Teléfono** (insensible al formato).
- **Importación CSV y Reingreso:** Si un registro en CSV tiene `total_vencido <= 0`, pasa a **'Al Día'**. Si un cliente estaba 'Al Día' pero el nuevo CSV trae deuda, se resetea automáticamente a **'Pendiente'** (Sin Gestión).
- **La clave de negocio universal es `legajo`**: NUNCA utilizar el ID interno para relaciones.
- **Segmento Moto:** Se identifica mediante la columna `moto` (TinyInt) en la tabla `clientes` (v1.8).
- **Auditoría (Borrado Lógico):** Eliminación mediante `UPDATE gestiones_historial SET oculta = 1`.

## Directrices para el Código Asistido (LLMs) 🤖
1. **Estilos:** Usar `TailwindCSS v2` localmente. Bordes `rounded-3xl` y sombras suaves.
2. **Consultas:** Uso obligatorio de **PDO con Marcadores**.
- **Navegación:** Se ha ELIMINADO el avance automático tras guardar una gestión (v2.7). El operador permanece en el mismo cliente para auditar su propia carga. Utilizar `navegarCliente(dir)` solo para flujo manual.
4. **Respuestas API:** Evitar trazas de error en texto; usar siempre JSON `success: false, message: "..."`.
5. **Comunicación Unificada:** WhatsApp se unifica en un solo botón/toggle que gestiona inteligentemente la redirección vía `redirigirWA(nro, msg)`.

## Directriz de Diseño Responsivo y UX Móvil (v2.7+) 📱
1. **Navigation:** Uso exclusivo de `Drawer` lateral en móviles.
2. **Tablas:** Solo un contenedor de scroll horizontal activo (`overflow-x-auto`) para evitar conflictos de recorte.
3. **Modales:** 100% viewport en móvil. Cabecera fija con dos filas de información y Tab Switcher entre Gestión e Historial.

---
*Última actualización de memoria: 2026-04-21 - Versión Operativa 2.7*
