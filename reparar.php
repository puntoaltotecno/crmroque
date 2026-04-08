<?php
/**
 * ARCHIVO: reparar.php
 * VERSIÓN: 1.8 - Agregado soporte para columna MOTO
 */
require_once 'db.php';

echo "<h2>Reparando Base de Datos en Hostinger...</h2>";
echo "<p style='color:blue;'>Versión 1.8 - Incluye migración de columna MOTO</p>";

try {
    $queries = [
        // Migraciones previas
        "ALTER TABLE gestiones_historial MODIFY COLUMN estado ENUM('promesa','no_responde','no_corresponde','llamar','numero_baja','otro','al_dia','carta') DEFAULT 'promesa'",
        "ALTER TABLE gestiones_historial ADD COLUMN IF NOT EXISTS oculta TINYINT(1) DEFAULT 0",
        "ALTER TABLE gestiones_historial ADD COLUMN IF NOT EXISTS intentos INT DEFAULT 0",
        "CREATE TABLE IF NOT EXISTS asignaciones (legajo VARCHAR(50) NOT NULL, usuario_id INT NOT NULL, PRIMARY KEY (legajo)) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS comunicados (id INT AUTO_INCREMENT PRIMARY KEY, usuario_destino_id INT DEFAULT NULL, mensaje TEXT NOT NULL, fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP, activo TINYINT(1) DEFAULT 1) ENGINE=InnoDB",
        "ALTER TABLE clientes ADD COLUMN IF NOT EXISTS localidad VARCHAR(100) AFTER c_cuotas",
        
        // ── NUEVA MIGRACIÓN v1.8: Columna MOTO ──
        "ALTER TABLE clientes ADD COLUMN IF NOT EXISTS moto TINYINT(1) DEFAULT 0 COMMENT 'Indica si el cliente tiene moto' AFTER telefonos",
    ];

    $indices = [
        // Índice para optimizar búsquedas por MOTO
        "CREATE INDEX IF NOT EXISTS idx_moto ON clientes(moto)"
    ];

    echo "<h3 style='color:#2563eb;'>Ejecutando migraciones de tablas...</h3>";
    
    foreach ($queries as $sql) {
        try {
            $pdo->exec($sql);
            echo "<p style='color:green'>✅ Ejecutado: " . substr($sql, 0, 60) . "...</p>";
        } catch (Exception $e) {
            // Si la columna ya existe, no es un error crítico
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "<p style='color:orange'>⚠️ Ya existía: " . substr($sql, 0, 60) . "...</p>";
            } else {
                echo "<p style='color:orange'>⚠️ Nota: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<h3 style='color:#2563eb;'>Creando índices de optimización...</h3>";
    
    foreach ($indices as $sql) {
        try {
            $pdo->exec($sql);
            echo "<p style='color:green'>✅ Índice creado</p>";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate key') !== false) {
                echo "<p style='color:orange'>⚠️ Índice ya existía</p>";
            } else {
                echo "<p style='color:orange'>⚠️ Nota: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // Verificar que la columna MOTO existe
    echo "<h3 style='color:#2563eb;'>Verificación final...</h3>";
    try {
        $result = $pdo->query("SELECT COUNT(*) as total, SUM(moto) as motos FROM clientes")->fetch();
        echo "<p style='color:green'>✅ Columna MOTO funcionando correctamente</p>";
        echo "<p style='color:#475569;'>📊 Total clientes: {$result['total']} | Clientes con MOTO: " . ($result['motos'] ?? 0) . "</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Error al verificar columna MOTO: " . $e->getMessage() . "</p>";
    }
    
    echo "<div style='background:#dcfce7;border:2px solid #16a34a;padding:1.5rem;border-radius:1rem;margin-top:2rem;'>";
    echo "<h3 style='color:#166534;'>✅ ¡Proceso completado con éxito!</h3>";
    echo "<p style='color:#166534;'><strong>Recuerda:</strong></p>";
    echo "<ul style='color:#166534;'>";
    echo "<li>✓ La columna MOTO está lista para usar</li>";
    echo "<li>✓ Puedes borrar este archivo (reparar.php) después de verificar</li>";
    echo "<li>✓ Actualiza tu CSV de importación agregando la columna 'moto' (valores: 1 o 0)</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h3 style='color:red'>Error crítico: " . $e->getMessage() . "</h3>";
}
?>
