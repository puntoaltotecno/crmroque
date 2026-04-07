<?php
require_once 'db.php';

echo "<h2>Reparando Base de Datos en Hostinger...</h2>";

try {
    $queries = [
        "ALTER TABLE gestiones_historial MODIFY COLUMN estado ENUM('promesa','no_responde','no_corresponde','llamar','numero_baja','otro','al_dia','carta') DEFAULT 'promesa'",
        "ALTER TABLE gestiones_historial ADD COLUMN IF NOT EXISTS oculta TINYINT(1) DEFAULT 0",
        "ALTER TABLE gestiones_historial ADD COLUMN IF NOT EXISTS intentos INT DEFAULT 0",
        "CREATE TABLE IF NOT EXISTS asignaciones (legajo VARCHAR(50) NOT NULL, usuario_id INT NOT NULL, PRIMARY KEY (legajo)) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS comunicados (id INT AUTO_INCREMENT PRIMARY KEY, usuario_destino_id INT DEFAULT NULL, mensaje TEXT NOT NULL, fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP, activo TINYINT(1) DEFAULT 1) ENGINE=InnoDB",
        "ALTER TABLE clientes ADD COLUMN IF NOT EXISTS localidad VARCHAR(100) AFTER c_cuotas"
    ];

    foreach ($queries as $sql) {
        try {
            $pdo->exec($sql);
            echo "<p style='color:green'>✅ Ejecutado: " . substr($sql, 0, 50) . "...</p>";
        } catch (Exception $e) {
            echo "<p style='color:orange'>⚠️ Nota: " . $e->getMessage() . "</p>";
        }
    }
    echo "<h3 style='color:blue'>¡Proceso terminado! Ya puedes borrar este archivo y probar el Tablero.</h3>";
} catch (Exception $e) {
    echo "<h3 style='color:red'>Error crítico: " . $e->getMessage() . "</h3>";
}