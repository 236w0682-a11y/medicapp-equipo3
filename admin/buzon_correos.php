<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol_nombre'] ?? '', ['Gerente', 'Sistemas'], true)) {
    redirect('login.php');
}

$pdo = getConnection();
$stmt = $pdo->query('SELECT * FROM notificaciones_correo ORDER BY created_at DESC LIMIT 50');
$correos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>MedicApp — Historial de Correos Automáticos</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; background: #f4f7f9; color: #1f2933; }
        header { background: #102a43; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        header a { color: #fff; text-decoration: none; margin-left: 1rem; font-size: .9rem; }
        .container { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }
        .mail-card { background: #fff; padding: 1.2rem; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,.06); margin-bottom: 1rem; border-left: 4px solid #2e7d32; }
        .empty-card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,.06); color: #52606d; }
        pre { white-space: pre-wrap; font-family: inherit; background: #f8fafc; padding: .8rem; border-radius: 5px; margin-bottom: 0; }
    </style>
</head>
<body>
<header>
    <div><strong>MedicApp</strong> — Registro de Notificaciones por Correo</div>
    <nav>
        <a href="<?= htmlspecialchars(url('admin/aprobaciones.php'), ENT_QUOTES, 'UTF-8') ?>">Aprobaciones</a>
        <a href="<?= htmlspecialchars(url('index.php'), ENT_QUOTES, 'UTF-8') ?>">Dashboard</a>
        <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>">Cerrar Sesión</a>
    </nav>
</header>

<main class="container">
    <h2>Correos Automáticos Enviados (<?= count($correos) ?>)</h2>

    <?php if (empty($correos)): ?>
        <div class="empty-card">
            Aún no hay correos registrados. Se generarán automáticamente cuando alguien se registre o cuando apruebes/rechaces una solicitud.
        </div>
    <?php else: ?>
        <?php foreach ($correos as $m): ?>
            <div class="mail-card">
                <strong>Para:</strong> <?= htmlspecialchars($m['destinatario_nombre'] . ' <' . $m['destinatario_email'] . '>', ENT_QUOTES, 'UTF-8') ?> |
                <small><?= htmlspecialchars($m['created_at'], ENT_QUOTES, 'UTF-8') ?></small><br>
                <strong>Asunto:</strong> <?= htmlspecialchars($m['asunto'], ENT_QUOTES, 'UTF-8') ?>
                <pre><?= htmlspecialchars($m['mensaje'], ENT_QUOTES, 'UTF-8') ?></pre>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
</body>
</html>