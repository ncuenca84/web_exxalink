<?php
/**
 * Panel de administración — Exxalink S.A.S.
 *
 * Registra certificados, genera un código no adivinable y un QR local.
 */
declare(strict_types=1);

require __DIR__ . '/../includes/security.php';
iniciar_sesion_segura();
cabeceras_seguridad();

// Control de acceso.
if (empty($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../conexion.php';
require __DIR__ . '/../phpqrcode/qrlib.php';

$msg    = '';
$msgTipo = 'valid';
$codigoGenerado = '';

/**
 * Genera un código único e impredecible: EXX-AAAA-XXXXXXXXXX (hex).
 */
function generar_codigo_unico(mysqli $conexion): string
{
    do {
        $codigo = 'EXX-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(5)));
        $stmt = $conexion->prepare('SELECT 1 FROM certificados WHERE codigo = ? LIMIT 1');
        $stmt->bind_param('s', $codigo);
        $stmt->execute();
        $existe = $stmt->get_result()->num_rows > 0;
        $stmt->close();
    } while ($existe);

    return $codigo;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $msg = 'Sesión expirada. Recargue la página e intente de nuevo.';
        $msgTipo = 'invalid';
    } else {
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $curso  = trim((string) ($_POST['curso'] ?? ''));
        $fecha  = trim((string) ($_POST['fecha'] ?? ''));

        // Validación de entradas.
        $fechaOk = (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha);
        if ($nombre === '' || $curso === '' || !$fechaOk) {
            $msg = 'Datos incompletos o fecha inválida.';
            $msgTipo = 'invalid';
        } elseif (mb_strlen($nombre) > 100 || mb_strlen($curso) > 150) {
            $msg = 'El nombre o el curso exceden la longitud permitida.';
            $msgTipo = 'invalid';
        } else {
            $codigo = generar_codigo_unico($conexion);

            $baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/') . '/';
            $url = $baseUrl . '?codigo=' . rawurlencode($codigo);

            // QR generado localmente (sin servicios externos).
            $dirQr = __DIR__ . '/../qrs';
            if (!is_dir($dirQr)) {
                mkdir($dirQr, 0755, true);
            }
            QRcode::png($url, $dirQr . '/' . $codigo . '.png', QR_ECLEVEL_L, 4, 2);

            $stmt = $conexion->prepare(
                'INSERT INTO certificados (codigo, nombre, curso, fecha_emision) VALUES (?,?,?,?)'
            );
            $stmt->bind_param('ssss', $codigo, $nombre, $curso, $fecha);
            $stmt->execute();
            $stmt->close();

            $codigoGenerado = $codigo;
            $msg = 'Certificado generado correctamente. Código: ' . $codigo;
        }
    }
}

// Últimos certificados emitidos.
$ultimos = [];
$rs = $conexion->query(
    'SELECT codigo, nombre, curso, fecha_emision FROM certificados ORDER BY id DESC LIMIT 10'
);
if ($rs) {
    $ultimos = $rs->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>Panel de Administración - Exxalink</title>
<link rel="stylesheet" href="../estilos.css">
</head>
<body>
<div class="container">
    <img src="../assets/logo_exxalink.png" class="logo" alt="Exxalink S.A.S.">
    <h2>Registrar Nuevo Certificado</h2>

    <?php if ($msg !== ''): ?>
        <p class="<?= e($msgTipo) ?>"><?= e($msg) ?></p>
    <?php endif; ?>

    <?php if ($codigoGenerado !== ''): ?>
        <p class="qr-preview">
            <img src="../qrs/<?= e($codigoGenerado) ?>.png" alt="QR del certificado <?= e($codigoGenerado) ?>">
        </p>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <?= csrf_input() ?>
        <input type="text" name="nombre" placeholder="Nombre completo" maxlength="100" required>
        <input type="text" name="curso" placeholder="Curso o capacitación" maxlength="150" required>
        <input type="date" name="fecha" required>
        <button type="submit">Registrar y Generar QR</button>
    </form>

    <?php if (!empty($ultimos)): ?>
        <h2>Últimos certificados</h2>
        <table class="cert-list">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($ultimos as $u): ?>
                <tr>
                    <td><?= e($u['codigo']) ?></td>
                    <td><?= e($u['nombre']) ?></td>
                    <td><?= e($u['fecha_emision']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="logout.php">Cerrar sesión</a></p>
</div>
</body>
</html>
