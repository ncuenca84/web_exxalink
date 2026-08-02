<?php
/**
 * Utilidades de seguridad compartidas — Exxalink S.A.S.
 *
 * Sesión endurecida, protección CSRF, escape de salida y cabeceras HTTP.
 */
declare(strict_types=1);

/**
 * Inicia la sesión con cookies endurecidas (HttpOnly, Secure, SameSite).
 */
function iniciar_sesion_segura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/certificados/',
        'httponly' => true,
        'secure'   => $https,
        'samesite' => 'Strict',
    ]);

    session_name('EXXASESSID');
    session_start();
}

/**
 * Devuelve (creando si hace falta) el token CSRF de la sesión.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/**
 * Campo oculto listo para insertar en un formulario.
 */
function csrf_input(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/**
 * Valida el token recibido contra el de la sesión (comparación segura).
 */
function csrf_validar(?string $token): bool
{
    return !empty($_SESSION['csrf'])
        && is_string($token)
        && hash_equals($_SESSION['csrf'], $token);
}

/**
 * Escapa texto para imprimir de forma segura en HTML (previene XSS).
 */
function e(?string $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Cabeceras de seguridad HTTP para todas las páginas del sistema.
 */
function cabeceras_seguridad(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header('X-XSS-Protection: 0');
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "img-src 'self' data:; style-src 'self'; script-src 'self'; "
        . "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'"
    );
}
