<?php
/**
 * Plantilla de configuración — Sistema de certificados Exxalink S.A.S.
 *
 * INSTRUCCIONES:
 *   1. Copia este archivo como  config.php  en el mismo directorio.
 *   2. Sustituye los valores por las credenciales REALES (ya rotadas).
 *   3. config.php NO debe versionarse ni ser accesible por web
 *      (ya está bloqueado en .gitignore y en certificados/.htaccess).
 *
 * Consulta SECURITY.md para el procedimiento completo de rotación.
 */

return [
    // --- Base de datos MySQL ---
    'db_host' => 'localhost',
    'db_user' => 'exxa_certificados',
    'db_pass' => 'COLOCA_AQUI_LA_CLAVE_ROTADA',
    'db_name' => 'exxa_certificados',

    // --- URL pública del verificador (con barra final) ---
    'base_url' => 'https://exxalink.com/certificados/',
];
