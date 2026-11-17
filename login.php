<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['usuario_id'])) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Complete todos los campos.';
    } else {
        try {
            $pdo = getConnection();
            $sql = 'SELECT u.id, u.nombre, u.apellido, u.password_hash, u.estado_aprobacion, u.motivo_rechazo, u.activo,
                           r.id AS rol_id, r.nombre AS rol_nombre
                    FROM usuarios u
                    INNER JOIN roles r ON u.rol_id = r.id
                    WHERE u.email = :email
                    LIMIT 1';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($password, $usuario['password_hash'])) {
                if ($usuario['estado_aprobacion'] === 'Pendiente') {
                    $error = 'Tu registro aún está pendiente de validación por Gerencia o Sistemas. Revisa tu correo electrónico.';
                } elseif ($usuario['estado_aprobacion'] === 'Rechazado') {
                    $motivo = $usuario['motivo_rechazo'] ? ' Motivo: ' . $usuario['motivo_rechazo'] : '';
                    $error = 'Tu solicitud de registro fue rechazada.' . $motivo;
                } elseif ((int)$usuario['activo'] !== 1) {
                    $error = 'Tu cuenta se encuentra desactivada.';
                } else {
                    session_regenerate_id(true);

                    $_SESSION['usuario_id']     = (int)$usuario['id'];
                    $_SESSION['usuario_nombre'] = $usuario['nombre'] . ' ' . $usuario['apellido'];
                    $_SESSION['rol_id']         = (int)$usuario['rol_id'];
                    $_SESSION['rol_nombre']     = $usuario['rol_nombre'];

                    redirect('index.php');
                }
            } else {
                $error = 'Correo electrónico o contraseña incorrectos.';
            }
        } catch (PDOException $e) {
            error_log('Error en login [MedicApp]: ' . $e->getMessage());
            $error = 'Error interno al procesar el inicio de sesión.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>MedicApp — Iniciar Sesión</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f4f7f9; display: flex; justify-content: center; align-items: center; min-height: 90vh; }
        .card { background: #fff; padding: 2rem; border-radius: 8px; width: 100%; max-width: 410px; box-shadow: 0 2px 10px rgba(0,0,0,.08); }
        h2 { margin-top: 0; color: #0f4c81; }
        label { display: block; margin-top: .9rem; font-weight: 600; font-size: .9rem; }
        input { width: 100%; padding: .6rem; margin-top: .3rem; border: 1px solid #ccd6dd; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; margin-top: 1.3rem; padding: .75rem; background: #0f4c81; color: #fff; border: none; border-radius: 5px; font-weight: 600; cursor: pointer; }
        .alert-err { background: #fdecea; color: #b71c1c; padding: .75rem; border-radius: 5px; margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="card">
    <h2>MedicApp — Acceso</h2>

    <?php if ($error !== ''): ?>
        <div class="alert-err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars(url('login.php'), ENT_QUOTES, 'UTF-8') ?>">
        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Ingresar</button>
    </form>
    <p style="text-align:center; margin-top:1rem;">¿No tienes cuenta? <a href="<?= htmlspecialchars(url('register.php'), ENT_QUOTES, 'UTF-8') ?>">Solicita tu registro aquí</a></p>
</div>
</body>
</html>