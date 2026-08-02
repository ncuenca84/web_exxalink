<?php
/**
 * Cierre de sesión seguro — Exxalink S.A.S.
 */
declare(strict_types=1);

require __DIR__ . '/../includes/security.php';
iniciar_sesion_segura();

// Vaciar y destruir la sesión, incluida su cookie.
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

header('Location: login.php');
exit;
