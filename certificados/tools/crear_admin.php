<?php
/**
 * Crear / actualizar un usuario administrador — Exxalink S.A.S.
 *
 * USO (solo por línea de comandos, NO por web):
 *     php crear_admin.php <usuario> <clave>
 *
 * Guarda la contraseña como hash seguro (password_hash / PASSWORD_DEFAULT).
 * Si el usuario ya existe, actualiza su contraseña.
 */
declare(strict_types=1);

// Blindaje: nunca ejecutar vía navegador.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo puede ejecutarse por línea de comandos.');
}

if ($argc < 3) {
    fwrite(STDERR, "Uso: php crear_admin.php <usuario> <clave>\n");
    exit(1);
}

$usuario = trim($argv[1]);
$clave   = $argv[2];

if ($usuario === '' || strlen($clave) < 10) {
    fwrite(STDERR, "El usuario no puede estar vacío y la clave debe tener al menos 10 caracteres.\n");
    exit(1);
}

require __DIR__ . '/../conexion.php';

$hash = password_hash($clave, PASSWORD_DEFAULT);

$stmt = $conexion->prepare(
    'INSERT INTO usuarios (usuario, clave_hash) VALUES (?, ?)
     ON DUPLICATE KEY UPDATE clave_hash = VALUES(clave_hash)'
);
$stmt->bind_param('ss', $usuario, $hash);
$stmt->execute();
$stmt->close();

echo "Usuario administrador '{$usuario}' creado/actualizado correctamente.\n";
