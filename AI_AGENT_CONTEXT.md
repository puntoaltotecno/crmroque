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

## Reglas de Negocio Vitales (v3.2) ⚙️
- **v3.2 — Clasificación MOTO en ABM:** Soporte manual para marcar/desmarcar clientes con deuda de motocicleta desde el panel de administración.
- **v3.1 — Módulo ABM Clientes:** Administración manual de la base de clientes (Alta, Baja, Modificación).
   - **Altas:** Permite crear nuevos clientes manualmente.
   - **Modificaciones:** Edición completa de ficha desde el modal o directamente desde la tabla principal.
   - **Bajas:** Restringidas a **Administradores** con confirmación mediante escritura del legajo.
   - **Asignación:** Permite la asignación manual de operadores a clientes.
- **v3.0 — Filtrado por Sucursal:** Se implementó un sistema de filtrado global por sucursal en el Tablero y listado de clientes, permitiendo análisis granulares por ubicación.
- **v2.9.2 — Métricas de Precisión:** El Tablero ahora calcula la cantidad de "Clientes Gestionados" (únicos) en lugar de gestiones totales para una medición real de cobertura de cartera.
- **v2.9.1 — Histórico y Analítica:** Se incorporó la tabla `historial_deuda` para capturar snapshots diarios/periódicos de deuda.
- **Atribución "Al día":** Los operadores reciben crédito por los éxitos "Al día" en sus carteras asignadas, independientemente de quién ejecutó la acción, validado mediante JOINs con `asignaciones`.
- **Navegación Secuencial (Modal):** El modal de gestión incluye botones de "Anterior" y "Siguiente" que permiten recorrer la lista filtrada actual sin cerrar la ventana.
- **Filtros Globales con Límites:** Búsqueda avanzada integrada en reportes con límites de visualización (Top 25/50/All).
- **Buscador Inteligente:** Rastreo por Legajo, DNI, Razón Social, Sucursal y **Número de Teléfono** (insensible al formato).
- **Importación CSV:** Los snapshots de deuda se disparan automáticamente en cada importación.

## Directrices para el Código Asistido (LLMs) 🤖
1. **Estilos:** Usar `TailwindCSS v2` localmente.
2. **Consultas:** Uso obligatorio de **PDO con Marcadores**.
3. **Métricas:** Para reportes de operador, siempre usar subconsultas sobre `asignaciones` para medir éxitos "Al día".
4. **Respuestas API:** Siempre JSON.
5. **Comunicación:** WhatsApp unificado con detección inteligente de Web/App.

---
*Última actualización de memoria: 2026-04-24 - Versión Operativa 3.2*
