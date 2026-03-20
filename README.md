# CRM.ROQUE — Sistema de Gestión de Cobranzas

Sistema web de seguimiento de cartera de clientes con asignación de operadores, historial de gestiones y panel de estadísticas.

---

## Tecnologías

- **Backend:** PHP 7.4+ con PDO
- **Base de datos:** MySQL / MariaDB
- **Frontend:** HTML + Tailwind CSS + JavaScript (Fetch API)
- **Servidor local:** XAMPP (Apache + MySQL)
- **Servidor producción:** Hostinger

---

## Estructura de archivos

```
/
├── index.php                   → App principal (login + dashboard)
├── login.php                   → Autenticación de usuarios
├── db.php                      → Conexión PDO (detecta local vs producción)
├── api_clientes.php            → Lista, busca, filtra y asigna clientes
├── api_gestion.php             → Guarda gestiones en el historial
├── api_historial.php           → Devuelve la línea de tiempo de un cliente
├── api_usuarios.php            → CRUD de operadores
├── api_asignar.php             → Asignación manual de legajos
├── api_importar_csv.php        → Importación masiva de clientes desde CSV
└── api_importar_asignaciones.php → Importación masiva de asignaciones desde CSV
```

---

## Base de datos

### Tablas principales

#### `clientes`
Datos fijos del cliente importados desde el sistema de origen.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT AUTO_INCREMENT | PK |
| l_entidad_id | INT UNIQUE | ID externo del sistema origen |
| legajo | VARCHAR(50) | **Clave de negocio principal** |
| razon_social | VARCHAR(150) | Nombre del cliente |
| nro_documento | VARCHAR(20) | DNI o CUIT |
| domicilio | VARCHAR(200) | Dirección + localidad |
| telefonos | VARCHAR(255) | Teléfonos separados por guión |
| dias_atraso | INT | Días de mora |
| mora | DECIMAL(15,2) | Importe de mora |
| total_vencido | DECIMAL(15,2) | Capital vencido |
| vencimiento | DATE | Fecha de vencimiento |
| ultimo_pago | DATE | Fecha del último pago registrado |
| c_cuotas | INT | Cantidad de cuotas pendientes |
| sucursal | VARCHAR(100) | Sucursal de origen |

> ⚠️ Las columnas `estado`, `fecha_promesa` y `monto_promesa` **no existen** en esta tabla.
> Esos datos se leen siempre desde la última gestión en `gestiones_historial`.

---

#### `gestiones_historial`
Registro cronológico de todas las gestiones. Es la **única fuente de verdad** para el estado de un cliente.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT AUTO_INCREMENT | PK |
| legajo | VARCHAR(50) | Vínculo con el cliente |
| usuario_id | INT | Operador que realizó la gestión |
| estado | ENUM | Estado de la gestión |
| fecha_promesa | DATE | Fecha acordada de pago |
| monto_promesa | DECIMAL(10,2) | Monto acordado |
| observaciones | TEXT | Detalle de la llamada |
| fecha_gestion | TIMESTAMP | Fecha y hora automática |

**Estados válidos:**
| Valor | Color en UI |
|-------|-------------|
| `promesa` | 🔵 Azul |
| `no_responde` | 🟠 Naranja |
| `no_corresponde` | 🔴 Rojo |
| `llamar` | 🟢 Verde |
| `numero_baja` | ⚫ Gris |
| `otro` | 🟣 Violeta |

---

#### `asignaciones`
Relaciona legajos con operadores.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| legajo | VARCHAR(50) PK | Legajo del cliente |
| usuario_id | INT FK | Operador asignado |

---

#### `usuarios`
Cuentas de acceso al sistema.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT AUTO_INCREMENT | PK |
| nombre | VARCHAR(100) | Nombre completo |
| email | VARCHAR(100) UNIQUE | Usuario de login |
| password | VARCHAR(255) | Hash bcrypt |
| rol | ENUM('admin','operador') | Nivel de acceso |

---

## Arquitectura de estados

El estado de un cliente **no se guarda en `clientes`**. Se obtiene dinámicamente:

```sql
-- Estado actual de un cliente = última fila de su historial
SELECT estado, fecha_promesa, monto_promesa
FROM gestiones_historial
WHERE legajo = '012887'
ORDER BY fecha_gestion DESC
LIMIT 1;
```

`api_clientes.php` hace este JOIN automáticamente con un subquery para todos los clientes de la lista.

---

## Semáforo visual

| Condición | Color del borde |
|-----------|----------------|
| `dias_atraso > 90` | 🔴 Rojo |
| Última gestión = `promesa` | 🟡 Amarillo |
| Cualquier otro caso | 🟢 Verde |

---

## Roles de usuario

### Administrador (`admin`)
- Ve todos los clientes de la cartera
- Puede asignar/reasignar legajos a operadores
- Puede filtrar por operador
- Puede importar CSV de clientes y asignaciones
- Puede crear, editar y eliminar operadores

### Operador (`operador`)
- Ve solo los clientes asignados a su usuario
- Puede gestionar y cargar observaciones
- No puede ver clientes de otros operadores

---

## Importación de clientes (CSV)

El archivo CSV debe tener exactamente estas **13 columnas** en orden:

```
l_entidad_id, legajo, razon_social, nro_documento, ultimo_pago,
c_cuotas, localidad, domicilio, dias_atraso, total_vencido,
vencimiento, sucursal, telefonos
```

- Delimitador: coma `,` o punto y coma `;` (se detecta automáticamente)
- Encoding: UTF-8
- La primera fila debe ser el encabezado
- `l_entidad_id` es la clave única — si ya existe, actualiza el registro

---

## Importación de asignaciones (CSV)

Formato de 2 columnas:

```
email_operador, legajo
```

El sistema busca el `usuario_id` según el email y crea o actualiza la asignación.

---

## Instalación local (XAMPP)

1. Copiar la carpeta del proyecto en `C:\xampp\htdocs\seguimiento\`
2. Crear la base de datos `u204222083_crm_ctacte_cli` en phpMyAdmin
3. Importar el archivo `.sql` con la estructura de tablas
4. Verificar que `db.php` tenga las credenciales correctas para local:
   ```php
   $db_user = 'root';
   $db_pass = '';
   ```
5. Acceder desde el navegador: `http://localhost/seguimiento/`

---

## Configuración de producción (Hostinger)

En `db.php` la conexión a producción se activa automáticamente cuando el host no es `localhost`:

```php
$db_user = 'u204222083_roque';
$db_pass = '!D^^^0iW';
$db_name = 'u204222083_crm_ctacte_cli';
```

---

## SQL de mantenimiento

```sql
-- Limpiar todos los datos (mantiene usuarios)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE gestiones_historial;
TRUNCATE TABLE asignaciones;
TRUNCATE TABLE clientes;
SET FOREIGN_KEY_CHECKS = 1;

-- Restaurar AUTO_INCREMENT si se rompe
ALTER TABLE gestiones_historial MODIFY id INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE clientes MODIFY id INT(11) NOT NULL AUTO_INCREMENT;

-- Ver el estado actual de cada cliente
SELECT c.legajo, c.razon_social, g.estado, g.fecha_gestion
FROM clientes c
LEFT JOIN gestiones_historial g ON g.legajo = c.legajo
LEFT JOIN (
    SELECT legajo, MAX(fecha_gestion) as ultima
    FROM gestiones_historial GROUP BY legajo
) ult ON g.legajo = ult.legajo AND g.fecha_gestion = ult.ultima
ORDER BY c.razon_social;
```

---

## Notas importantes

- El campo `legajo` es la **clave de negocio** que vincula todas las tablas. Nunca usar `id` para relacionar datos entre tablas.
- El campo `id` de `clientes` puede ser 0 en registros importados antes de agregar `AUTO_INCREMENT`. Toda la lógica crítica usa `legajo`.
- Los passwords se almacenan con `password_hash()` de PHP (bcrypt). Nunca en texto plano.
