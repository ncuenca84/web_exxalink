<?php
/**
 * Verificación pública de certificados — Exxalink S.A.S.
 */
declare(strict_types=1);

require __DIR__ . '/includes/security.php';
cabeceras_seguridad();
require __DIR__ . '/conexion.php';

$codigo = isset($_GET['codigo']) ? trim((string) $_GET['codigo']) : '';

$certificado = null;
$buscado = false;

if ($codigo !== '') {
    $buscado = true;
    $stmt = $conexion->prepare(
        'SELECT nombre, curso, fecha_emision FROM certificados WHERE codigo = ? LIMIT 1'
    );
    $stmt->bind_param('s', $codigo);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $certificado = $res->fetch_assoc();
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>Verificación de Certificado | Exxalink S.A.S.</title>
<link rel="stylesheet" href="estilos.css">
</head>
<body>
<main class="container">
    <img src="assets/logo_exxalink.png" class="logo" alt="Exxalink S.A.S.">
    <h1>Verificación de Certificado</h1>
    <p class="subtitle">Confirme la autenticidad de un certificado emitido por Exxalink S.A.S.</p>

    <form method="get" autocomplete="off" class="search">
        <input type="text" name="codigo" placeholder="Ingrese el código del certificado"
               value="<?= e($codigo) ?>" maxlength="60" required aria-label="Código del certificado">
        <button type="submit">Verificar</button>
    </form>

<?php if ($buscado): ?>
    <?php if ($certificado !== null): ?>
        <div class="result result--ok">
            <span class="badge badge--ok">✔ Certificado válido</span>
            <dl class="cert-details">
                <div class="row"><dt>Nombre</dt><dd><?= e($certificado['nombre']) ?></dd></div>
                <div class="row"><dt>Curso</dt><dd><?= e($certificado['curso']) ?></dd></div>
                <div class="row"><dt>Fecha de emisión</dt><dd><?= e($certificado['fecha_emision']) ?></dd></div>
                <div class="row"><dt>Código</dt><dd><?= e($codigo) ?></dd></div>
            </dl>
            <p class="issuer">Emitido por <strong>Exxalink S.A.S.</strong></p>
        </div>
    <?php else: ?>
        <div class="result result--bad">
            <span class="badge badge--bad">✕ Certificado no válido</span>
            <p class="muted">No encontramos ningún certificado con ese código. Verifíquelo e intente nuevamente.</p>
        </div>
    <?php endif; ?>
<?php endif; ?>
</main>
<footer class="page-footer">Sistema de verificación de certificados · Exxalink S.A.S.</footer>
</body>
</html>
