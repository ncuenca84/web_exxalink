<?php
/**
 * Acceso administrativo — Exxalink S.A.S.
 *
 * Autenticación contra la tabla `usuarios` (contraseñas con password_hash()).
 * Incluye protección CSRF, regeneración de sesión y limitación de intentos.
 */
declare(strict_types=1);

require __DIR__ . '/../includes/security.php';
iniciar_sesion_segura();
cabeceras_seguridad();

// Ya autenticado -> al panel.
if (!empty($_SESSION['admin'])) {
    header('Location: panel.php');
    exit;
}

const MAX_INTENTOS     = 5;    // intentos antes del bloqueo
const BLOQUEO_SEGUNDOS = 900;  // 15 minutos
// Hash "señuelo" (bcrypt válido) para que el login tarde lo mismo exista o no
// el usuario, evitando la enumeración de usuarios por tiempo de respuesta.
const HASH_SENUELO = '$2y$12$Pay1jExYS2KkEJekgRJR/OdqPJIe8t1FL98f0Dpk0Gg6cYlEMqsbm';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/../conexion.php';
    $ip = cliente_ip();

    // --- Control de fuerza bruta por IP (almacén persistente) ---
    $ipIntentos = 0; $ipUltimo = 0;
    try {
        $q = $conexion->prepare('SELECT intentos, ultimo FROM intentos_login WHERE ip = ? LIMIT 1');
        $q->bind_param('s', $ip);
        $q->execute();
        if ($r = $q->get_result()->fetch_assoc()) {
            $ipIntentos = (int) $r['intentos'];
            $ipUltimo   = (int) $r['ultimo'];
        }
        $q->close();
    } catch (\Throwable $e) {
        // Si la tabla no existe aún, no rompemos el login (solo se pierde el
        // throttle hasta ejecutar db/intentos_login.sql).
        error_log('intentos_login no disponible: ' . $e->getMessage());
    }

    $bloqueado = $ipIntentos >= MAX_INTENTOS && (time() - $ipUltimo) < BLOQUEO_SEGUNDOS;

    if ($bloqueado) {
        $restante = (int) ceil((BLOQUEO_SEGUNDOS - (time() - $ipUltimo)) / 60);
        $error = "Demasiados intentos fallidos. Espere {$restante} minuto(s).";
    } elseif (!csrf_validar($_POST['csrf'] ?? null)) {
        $error = 'Sesión expirada. Recargue la página e intente de nuevo.';
    } else {
        $usuario = trim((string) ($_POST['usuario'] ?? ''));
        $clave   = (string) ($_POST['clave'] ?? '');

        $stmt = $conexion->prepare(
            'SELECT id, usuario, clave_hash FROM usuarios WHERE usuario = ? LIMIT 1'
        );
        $stmt->bind_param('s', $usuario);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Verificación de tiempo constante: si no hay usuario, se compara igual
        // contra un hash señuelo para que la respuesta tarde lo mismo.
        $ok = $fila
            ? password_verify($clave, $fila['clave_hash'])
            : (password_verify($clave, HASH_SENUELO) && false);

        if ($ok) {
            // Éxito: renovar id de sesión (previene fijación de sesión).
            session_regenerate_id(true);
            // Limpiar el contador de intentos de esta IP.
            try {
                $del = $conexion->prepare('DELETE FROM intentos_login WHERE ip = ?');
                $del->bind_param('s', $ip);
                $del->execute();
                $del->close();
            } catch (\Throwable $e) { /* tabla ausente: ignorar */ }

            $_SESSION['admin']          = true;
            $_SESSION['usuario_id']     = (int) $fila['id'];
            $_SESSION['usuario_nombre'] = $fila['usuario'];

            // Rehash si el algoritmo por defecto cambió.
            if (password_needs_rehash($fila['clave_hash'], PASSWORD_DEFAULT)) {
                $nuevo = password_hash($clave, PASSWORD_DEFAULT);
                $up = $conexion->prepare('UPDATE usuarios SET clave_hash = ? WHERE id = ?');
                $up->bind_param('si', $nuevo, $fila['id']);
                $up->execute();
                $up->close();
            }

            header('Location: panel.php');
            exit;
        }

        // Fallo: registrar intento por IP (reinicia el contador si expiró la ventana).
        $ahora = time();
        try {
            $ins = $conexion->prepare(
                'INSERT INTO intentos_login (ip, intentos, ultimo) VALUES (?, 1, ?)
                 ON DUPLICATE KEY UPDATE
                   intentos = IF(? - ultimo >= ' . BLOQUEO_SEGUNDOS . ', 1, intentos + 1),
                   ultimo = ?'
            );
            $ins->bind_param('siii', $ip, $ahora, $ahora, $ahora);
            $ins->execute();
            $ins->close();
        } catch (\Throwable $e) { /* tabla ausente: ignorar */ }

        // Mensaje genérico (no revela si el usuario existe).
        $error = 'Credenciales incorrectas.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>Login - Panel Exxalink</title>
<link rel="stylesheet" href="../estilos.css">
</head>
<body>
<main class="container">
    <img src="../assets/logo_exxalink.png" class="logo" alt="Exxalink S.A.S.">
    <h1>Acceso Administrativo</h1>
    <p class="subtitle">Ingrese sus credenciales para gestionar certificados.</p>

    <?php if ($error !== ''): ?>
        <div class="alert alert--bad"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off" class="field-group">
        <?= csrf_input() ?>
        <input type="text" name="usuario" placeholder="Usuario" required autofocus aria-label="Usuario">
        <input type="password" name="clave" placeholder="Contraseña" required aria-label="Contraseña">
        <button type="submit" class="btn--block">Ingresar</button>
    </form>
</main>
<footer class="page-footer">Panel administrativo · Exxalink S.A.S.</footer>
</body>
</html>
