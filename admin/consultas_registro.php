<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol_nombre'] ?? '', ['Doctor', 'Recepcionista'], true)) {
    redirect('login.php');
}

$pdo     = getConnection();
$userId  = (int)$_SESSION['usuario_id'];
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $consultaId = filter_input(INPUT_POST, 'consulta_id', FILTER_VALIDATE_INT);
    $respuesta  = trim($_POST['respuesta'] ?? '');

    if ($consultaId && $respuesta !== '') {
        $stmtUpd = $pdo->prepare(
            'UPDATE consultas_validacion
             SET respuesta = :resp, respondido_at = NOW()
             WHERE id = :id AND destinatario_id = :dest_id'
        );
        $stmtUpd->execute([
            ':resp'    => $respuesta,
            ':id'      => $consultaId,
            ':dest_id' => $userId,
        ]);
        $mensaje = 'Respuesta enviada correctamente a Gerencia/Sistemas.';
    }
}

$stmt = $pdo->prepare(
    "SELECT cv.id, cv.mensaje, cv.respuesta, cv.created_at,
            CONCAT(rem.nombre, ' ', rem.apellido) AS remitente_nombre,
            rrem.nombre AS remitente_rol,
            CONCAT(pend.nombre, ' ', pend.apellido) AS paciente_nombre,
            pend.email AS paciente_email,
            rpend.nombre AS rol_solicitado
     FROM consultas_validacion cv
     INNER JOIN usuarios rem ON cv.remitente_id = rem.id
     INNER JOIN roles rrem ON rem.rol_id = rrem.id
     INNER JOIN usuarios pend ON cv.usuario_pendiente_id = pend.id
     INNER JOIN roles rpend ON pend.rol_id = rpend.id
     WHERE cv.destinatario_id = :uid
     ORDER BY cv.created_at DESC"
);
$stmt->execute([':uid' => $userId]);
$consultas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>MedicApp — Validación de Pacientes / Registros</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; background: #f4f7f9; color: #1f2933; }
        header { background: #1b365d; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        header a { color: #fff; text-decoration: none; margin-left: 1rem; font-size: .9rem; }
        .container { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; padding: 1.3rem; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,.06); margin-bottom: 1rem; border-left: 4px solid #ef6c00; }
        .alert-ok { background: #e8f5e9; color: #1b5e20; padding: .75rem; border-radius: 5px; margin-bottom: 1rem; }
        input[type="text"] { width: 100%; padding: .55rem; border: 1px solid #ccd6dd; border-radius: 4px; box-sizing: border-box; margin-top: .5rem; }
        button { margin-top: .5rem; padding: .5rem 1rem; background: #0f4c81; color: #fff; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>
<header>
    <div><strong>MedicApp</strong> — Solicitudes de Validación desde Gerencia/Sistemas</div>
    <nav>
        <a href="<?= htmlspecialchars(url('index.php'), ENT_QUOTES, 'UTF-8') ?>">Volver al Dashboard</a>
        <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>">Cerrar Sesión</a>
    </nav>
</header>

<main class="container">
    <h2>Consultas de Verificación de Identidad</h2>

    <?php if ($mensaje !== ''): ?>
        <div class="alert-ok"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (empty($consultas)): ?>
        <div class="card">No tienes consultas pendientes de Gerencia o Sistemas.</div>
    <?php else: ?>
        <?php foreach ($consultas as $c): ?>
            <div class="card">
                <strong>De:</strong> <?= htmlspecialchars($c['remitente_nombre'] . ' (' . $c['remitente_rol'] . ')', ENT_QUOTES, 'UTF-8') ?><br>
                <strong>Solicitante:</strong> <?= htmlspecialchars($c['paciente_nombre'] . ' — ' . $c['paciente_email'] . ' [' . $c['rol_solicitado'] . ']', ENT_QUOTES, 'UTF-8') ?>
                <p style="background:#f8fafc; padding:.6rem; border-radius:4px;">
                    <?= htmlspecialchars($c['mensaje'], ENT_QUOTES, 'UTF-8') ?>
                </p>

                <?php if (!empty($c['respuesta'])): ?>
                    <div style="color:#1b5e20;"><strong>Tu respuesta enviada:</strong> <?= htmlspecialchars($c['respuesta'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php else: ?>
                    <form method="POST" action="<?= htmlspecialchars(url('admin/consultas_registro.php'), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="consulta_id" value="<?= (int)$c['id'] ?>">
                        <input type="text" name="respuesta" required placeholder="Ej. Sí, es mi paciente / personal autorizado, favor de aprobar.">
                        <button type="submit">Responder a Gerencia/Sistemas</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
</body>
</html>