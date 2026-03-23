# CRM.ROQUE — Sistema de Gestión de Cobranzas

Sistema web de seguimiento de cartera de clientes con asignación de operadores, historial de gestiones, panel de estadísticas (Tablero) y control de accesos.

---

## Tecnologías

- **Backend:** PHP 7.4+ con PDO (Sin frameworks)
- **Base de datos:** MySQL / MariaDB
- **Frontend:** HTML + Tailwind CSS + JavaScript (Fetch API)
- **Servidor local:** XAMPP (Apache + MySQL)
- **Servidor producción:** Hostinger

---

## Estructura de archivos

```text
/
├── index.php                   → App principal (login, cartera activa, equipo y tablero)
├── login.php                   → Autenticación de usuarios
├── db.php                      → Conexión PDO (detecta local vs producción)
├── api_clientes.php            → Lista, busca, filtra y asigna clientes
├── api_gestion.php             → Guarda gestiones en el historial (con validación de roles)
├── api_historial.php           → Devuelve la línea de tiempo de un cliente
├── api_usuarios.php            → CRUD de operadores
├── api_asignar.php             → Asignación manual de legajos
├── api_importar_csv.php        → Importación masiva de clientes desde CSV (con reglas de reingreso)
├── api_importar_asignaciones.php → Importación masiva de asignaciones desde CSV
└── api_dashboard.php           → Extrae métricas globales, estados de cartera y rendimiento por operador
Roles y Permisos
El sistema maneja 3 niveles de acceso estandarizados:

Admin (Administrador Total):

Acceso total al sistema.

Acceso al Tablero (Dashboard) con métricas financieras y de rendimiento operativo.

Puede importar clientes y asignaciones vía CSV.

Puede gestionar el equipo de usuarios (crear, editar, eliminar).

Acceso a acciones masivas (incluyendo eliminación de legajos).

Colaborador (Coordinador):

Acceso al Tablero (Dashboard) para monitorear el rendimiento del equipo.

Puede asignar carteras y ver a todo el personal.

No puede importar CSV ni eliminar usuarios/legajos.

Operador (Base):

Solo visualiza la cartera general, ocultando métricas financieras globales.

No tiene acceso a la pestaña del Tablero de métricas.

Restricción de Seguridad: No tiene permisos para clasificar a un cliente como "Al Día". Si un cliente ya está "Al Día", el sistema bloquea el formulario impidiendo que el operador lo edite.

Reglas de Negocio y Automatizaciones
Tablero de Rendimiento: Calcula en tiempo real la efectividad de los operadores (Promesas logradas / Clientes gestionados), mostrando colores semánticos (Verde >30%, Naranja >15%, Rojo <15%).

Lógica de Importación CSV (Reingresos): Si un cliente estaba marcado como "Al Día" pero vuelve a aparecer en un nuevo CSV importado, el sistema asume que volvió a tener deuda y le inserta automáticamente un estado NULL (Pendiente / Sin gestión).

Lógica de Importación CSV (Salidas): Si un cliente que estaba en la base de datos ya no figura en el CSV importado, el sistema lo pasa automáticamente al estado "Al Día".

Base de datosTablas principalesclientesDatos fijos del cliente importados desde el sistema de origen.CampoTipoDescripciónidINT AUTO_INCREMENTPKl_entidad_idINT UNIQUEID externo del sistema origenlegajoVARCHAR(50)Clave de negocio principalrazon_socialVARCHAR(150)Nombre del deudornro_documentoVARCHAR(50)DNI / CUITtotal_vencidoDECIMAL(10,2)Monto adeudadodias_atrasoINTDías en moraultimo_pagoDATEFecha

gestiones_historialLínea de tiempo de cada cliente. La gestión con el id más alto determina el "estado actual" del cliente.CampoTipoDescripciónidINT AUTO_INCREMENTPKlegajoVARCHAR(50)FK lógica a clientes.legajousuario_idINTFK a usuarios.id (quién hizo la gestión)estadoENUM'promesa', 'no_responde', 'no_corresponde', 'llamar', 'numero_baja', 'otro', 'al_dia', 'carta'fecha_promesaDATESolo si estado = 'promesa'monto_promesaDECIMALSolo si estado = 'promesa'observacionesTEXTNotas del operadorfecha_gestionDATETIMETimestamp automático

Nota: El estado NULL o la ausencia de registros en esta tabla se interpreta en el frontend como estado "Pendiente").asignacionesRelación 1 a 1 entre cliente y operador actual.CampoTipoDescripciónidINT AUTO_INCREMENTPKlegajoVARCHAR(50)FK lógica a clientes.legajousuario_idINTFK a usuarios.id

usuariosPersonal del sistema.CampoTipoDescripciónidINT AUTO_INCREMENTPKusuarioVARCHAR(50)Email o username para loginclaveVARCHAR(255)Hash de la contraseñanombreVARCHAR(100)Nombre para mostrar en UIrolENUM'admin', 'operador', 'colaborador'activoTINYINT(1)1=Activo, 0=Bloqueado

Bugfix histórico (Login)
Síntoma: No se podía iniciar sesión. El botón se quedaba trabado o recargaba el formulario correctamente.
Problema: Al hacer clic en "Entrar", el formulario se enviaba como GET en lugar de POST, lo que causaba que login.php nunca procesara las credenciales (requiere REQUEST_METHOD === 'POST'). El handler de JavaScript que interceptaba el submit estaba dentro del bloque PHP <?php else: ?> (es decir, solo se cargaba cuando el usuario ya estaba autenticado), por lo que nunca estaba disponible en la pantalla de login.

Solución aplicada en index.php:

Se agregó onsubmit="handleLogin(event)" directamente en el <form> del login.

Se creó la función handleLogin() en un <script> separado al final del </body>, garantizando que esté disponible siempre, independientemente del estado de sesión.

Se eliminó el handler anterior que era inalcanzable.

Nota: La tabla usuarios tiene columnas activo y created_at que ya están documentadas en la sección de BD. El campo activo actualmente no se valida en login.php — un usuario con activo = 0 puede seguir ingresando. Considerar agregar AND activo = 1 al query de login si se necesita deshabilitar cuentas.

Notas importantes
El campo legajo es la clave de negocio que vincula todas las tablas. Nunca usar id para relacionar datos entre tablas.

El campo id de clientes puede ser 0 en registros importados antes de agregar AUTO_INCREMENT. Toda la lógica crítica usa legajo.

Los passwords se almacenan con password_hash() de PHP (bcrypt). Nunca en texto plano.
