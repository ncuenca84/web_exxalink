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
<div class="container">
    <img src="assets/logo_exxalink.png" class="logo" alt="Exxalink S.A.S.">
    <h2>Verificación de Certificado</h2>

    <form method="get" autocomplete="off">
        <input type="text" name="codigo" placeholder="Ingrese el código del certificado"
               value="<?= e($codigo) ?>" maxlength="60" required>
        <button type="submit">Verificar</button>
    </form>

<?php if ($buscado): ?>
    <?php if ($certificado !== null): ?>
        <p class="valid">✅ Certificado válido</p>
        <p><strong>Nombre:</strong> <?= e($certificado['nombre']) ?></p>
        <p><strong>Curso:</strong> <?= e($certificado['curso']) ?></p>
        <p><strong>Fecha de emisión:</strong> <?= e($certificado['fecha_emision']) ?></p>
        <p>Emitido por: <strong>Exxalink S.A.S.</strong></p>
    <?php else: ?>
        <p class="invalid">❌ Certificado no válido</p>
    <?php endif; ?>
<?php endif; ?>
</div>
</body>
</html>
