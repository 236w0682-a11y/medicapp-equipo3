<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/mail_helper.php';

if (isset($_SESSION['usuario_id'])) {
    redirect('index.php');
}

$pdo = getConnection();
$errores = [];
$exito = '';

$stmtRoles = $pdo->query('SELECT id, nombre FROM roles ORDER BY id ASC');
$roles = $stmtRoles->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $telefono = trim($_POST['telefono'] ?? '');
    $rolId    = (int)($_POST['rol_id'] ?? 0);
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    if ($nombre === '' || $apellido === '') {
        $errores[] = 'El nombre y el apellido son obligatorios.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingrese un correo electrónico válido.';
    }
    if (strlen($password) < 8) {
        $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    }
    if ($password !== $confirm) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    $rolSeleccionado = null;
    foreach ($roles as $r) {
        if ((int)$r['id'] === $rolId) {
            $rolSeleccionado = $r;
            break;
        }
    }
    if ($rolSeleccionado === null) {
        $errores[] = 'Seleccione un rol válido.';
    }

    if (empty($errores)) {
        try {
            $stmtCheck = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
            $stmtCheck->execute([':email' => $email]);

            if ($stmtCheck->fetch()) {
                $errores[] = 'Este correo electrónico ya tiene una solicitud o cuenta registrada.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO usuarios (rol_id, nombre, apellido, email, password_hash, telefono, estado_aprobacion, activo)
                        VALUES (:rol_id, :nombre, :apellido, :email, :password_hash, :telefono, 'Pendiente', 0)";
                $stmtInsert = $pdo->prepare($sql);
                $stmtInsert->execute([
                    ':rol_id'        => $rolId,
                    ':nombre'        => $nombre,
                    ':apellido'      => $apellido,
                    ':email'         => $email,
                    ':password_hash' => $passwordHash,
                    ':telefono'      => $telefono !== '' ? $telefono : null,
                ]);

                $nombreCompleto = $nombre . ' ' . $apellido;
                $cuerpoCorreo = "Estimado(a) {$nombreCompleto},\n\n"
                    . "Muchas gracias por registrarte en MedicApp con el rol de {$rolSeleccionado['nombre']}.\n"
                    . "Tu solicitud se encuentra actualmente en proceso de validación de identidad por parte de Gerencia o Sistemas.\n"
                    . "En cuanto tu cuenta sea aprobada, recibirás un correo de confirmación con el enlace oficial de acceso.\n\n"
                    . "Atentamente,\nAdministración — MedicApp";

                enviarNotificacionCorreo(
                    $pdo,
                    $email,
                    $nombreCompleto,
                    'MedicApp — Solicitud de Registro Recibida',
                    $cuerpoCorreo
                );

                $exito = "Muchas gracias por registrarte en MedicApp, {$nombreCompleto}. Hemos recibido tu solicitud y te enviaremos un correo electrónico de aprobación una vez que Gerencia o Sistemas valide tu identidad.";

                // Limpiar los campos del formulario tras el registro exitoso
                $_POST = [];
            }
        } catch (PDOException $e) {
            error_log('Error en registro [MedicApp]: ' . $e->getMessage());
            $errores[] = 'Ocurrió un error interno al procesar tu solicitud.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>MedicApp — Solicitud de Registro</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f4f7f9; display: flex; justify-content: center; padding: 2rem; }
        .card { background: #fff; padding: 2rem; border-radius: 8px; width: 100%; max-width: 460px; box-shadow: 0 2px 10px rgba(0,0,0,.08); }
        h2 { margin-top: 0; color: #0f4c81; }
        label { display: block; margin-top: .85rem; font-weight: 600; font-size: .9rem; }
        input, select { width: 100%; padding: .6rem; margin-top: .3rem; border: 1px solid #ccd6dd; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; margin-top: 1.3rem; padding: .75rem; background: #0f4c81; color: #fff; border: none; border-radius: 5px; font-weight: 600; cursor: pointer; }
        .alert-err { background: #fdecea; color: #b71c1c; padding: .75rem; border-radius: 5px; margin-bottom: 1rem; }
        .alert-ok { background: #e8f5e9; color: #1b5e20; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; border-left: 4px solid #2e7d32; line-height: 1.45; }
        .info-box { background: #e3f2fd; color: #0d47a1; padding: .7rem; border-radius: 5px; font-size: .85rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="card">
    <h2>MedicApp — Registro</h2>

    <div class="info-box">
        Por seguridad clínica, todas las cuentas nuevas pasan por validación de identidad por parte de <strong>Gerencia</strong> o <strong>Sistemas</strong>.
    </div>

    <?php if (!empty($errores)): ?>
        <div class="alert-err">
            <ul>
                <?php foreach ($errores as $err): ?>
                    <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($exito !== ''): ?>
        <div class="alert-ok">
            <strong>¡Solicitud recibida!</strong><br>
            <?= htmlspecialchars($exito, ENT_QUOTES, 'UTF-8') ?><br><br>
            <a href="<?= htmlspecialchars(url('login.php'), ENT_QUOTES, 'UTF-8') ?>">Volver al inicio de sesión</a>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars(url('register.php'), ENT_QUOTES, 'UTF-8') ?>" novalidate>
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" required value="<?= htmlspecialchars($_POST['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <label for="apellido">Apellido</label>
        <input type="text" id="apellido" name="apellido" required value="<?= htmlspecialchars($_POST['apellido'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <label for="telefono">Teléfono de contacto</label>
        <input type="text" id="telefono" name="telefono" required value="<?= htmlspecialchars($_POST['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <label for="rol_id">Rol solicitado</label>
        <select id="rol_id" name="rol_id" required>
            <option value="">-- Seleccione un rol --</option>
            <?php foreach ($roles as $rol): ?>
                <option value="<?= (int)$rol['id'] ?>" <?= ((int)($_POST['rol_id'] ?? 0) === (int)$rol['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($rol['nombre'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="password">Contraseña (mínimo 8 caracteres)</label>
        <input type="password" id="password" name="password" required>

        <label for="password_confirm">Confirmar contraseña</label>
        <input type="password" id="password_confirm" name="password_confirm" required>

        <button type="submit">Solicitar Registro</button>
    </form>
    <p style="text-align:center; margin-top:1rem;">¿Ya tienes cuenta aprobada? <a href="<?= htmlspecialchars(url('login.php'), ENT_QUOTES, 'UTF-8') ?>">Inicia sesión</a></p>
</div>
</body>
</html>