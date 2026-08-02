<?php
/**
 * Conexión a base de datos MySQL - Exxalink S.A.S.
 *
 * Las credenciales se cargan desde config.php (fuera del control de versiones).
 * Copia config.example.php -> config.php y coloca las credenciales rotadas.
 */
declare(strict_types=1);

$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    error_log('Falta el archivo de configuración: ' . $configFile);
    http_response_code(500);
    exit('Error de configuración del servidor. Contacte al administrador.');
}

/** @var array $config */
$config = require $configFile;

// Que los errores de MySQL se lancen como excepciones (no como warnings que
// filtren credenciales o rutas al usuario final).
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conexion = new mysqli(
        $config['db_host'],
        $config['db_user'],
        $config['db_pass'],
        $config['db_name']
    );
    $conexion->set_charset('utf8mb4');
} catch (\Throwable $e) {
    // Detalle solo al log del servidor; mensaje genérico al usuario.
    error_log('Error de conexión a MySQL: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo conectar a la base de datos. Intente más tarde.');
}
