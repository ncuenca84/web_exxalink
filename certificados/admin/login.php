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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Control de fuerza bruta (por sesión) ---
    $intentos    = $_SESSION['login_intentos'] ?? 0;
    $ultimoFallo = $_SESSION['login_ultimo'] ?? 0;

    if ($intentos >= MAX_INTENTOS && (time() - $ultimoFallo) < BLOQUEO_SEGUNDOS) {
        $restante = (int) ceil((BLOQUEO_SEGUNDOS - (time() - $ultimoFallo)) / 60);
        $error = "Demasiados intentos fallidos. Espere {$restante} minuto(s).";
    } elseif (!csrf_validar($_POST['csrf'] ?? null)) {
        $error = 'Sesión expirada. Recargue la página e intente de nuevo.';
    } else {
        $usuario = trim((string) ($_POST['usuario'] ?? ''));
        $clave   = (string) ($_POST['clave'] ?? '');

        require __DIR__ . '/../conexion.php';
        $stmt = $conexion->prepare(
            'SELECT id, usuario, clave_hash FROM usuarios WHERE usuario = ? LIMIT 1'
        );
        $stmt->bind_param('s', $usuario);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($fila && password_verify($clave, $fila['clave_hash'])) {
            // Éxito: renovar id de sesión (previene fijación de sesión).
            session_regenerate_id(true);
            unset($_SESSION['login_intentos'], $_SESSION['login_ultimo']);
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

        // Fallo: mensaje genérico (no revela si el usuario existe).
        $_SESSION['login_intentos'] = $intentos + 1;
        $_SESSION['login_ultimo']   = time();
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
<div class="container">
    <img src="../assets/logo_exxalink.png" class="logo" alt="Exxalink S.A.S.">
    <h2>Acceso Administrativo</h2>

    <?php if ($error !== ''): ?>
        <p class="invalid"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <?= csrf_input() ?>
        <input type="text" name="usuario" placeholder="Usuario" required autofocus>
        <input type="password" name="clave" placeholder="Contraseña" required>
        <button type="submit">Ingresar</button>
    </form>
</div>
</body>
</html>
