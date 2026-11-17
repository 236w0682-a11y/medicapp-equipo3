<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['rol_nombre'])) {
    redirect('login.php');
}

$pdo       = getConnection();
$usuarioId = (int)$_SESSION['usuario_id'];
$rolNombre = $_SESSION['rol_nombre'];
$mensaje   = $_GET['msg'] ?? '';

if ($rolNombre === 'Paciente') {
    $sql = "SELECT c.id, c.fecha_cita, c.hora_inicio, c.hora_fin, c.costo_acordado,
                   c.estado, c.motivo_consulta, c.notas_medicas,
                   sm.especialidad, sm.medico
            FROM citas c
            INNER JOIN servicios_medicos sm ON c.servicio_id = sm.id
            WHERE c.paciente_id = :paciente_id
            ORDER BY c.fecha_cita DESC, c.hora_inicio DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':paciente_id' => $usuarioId]);
} else {
    $sql = "SELECT c.id, c.fecha_cita, c.hora_inicio, c.hora_fin, c.costo_acordado,
                   c.estado, c.motivo_consulta, c.notas_medicas,
                   CONCAT(u.nombre, ' ', u.apellido) AS paciente_nombre,
                   sm.especialidad, sm.medico
            FROM citas c
            INNER JOIN usuarios u ON c.paciente_id = u.id
            INNER JOIN servicios_medicos sm ON c.servicio_id = sm.id
            ORDER BY c.fecha_cita DESC, c.hora_inicio DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}

$historial = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>MedicApp — Historial Médico</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; background: #f4f7f9; color: #1f2933; }
        header { background: #0f4c81; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        header a { color: #fff; text-decoration: none; margin-left: 1rem; font-size: .9rem; }
        .container { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .btn { padding: .55rem 1rem; background: #0f4c81; color: #fff; text-decoration: none; border-radius: 5px; font-weight: 600; font-size: .9rem; }
        .alert-ok { background: #e8f5e9; color: #1b5e20; padding: .75rem 1rem; border-radius: 5px; margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        th, td { padding: .85rem; text-align: left; border-bottom: 1px solid #e5e9f0; vertical-align: top; font-size: .9rem; }
        th { background: #e9eff5; font-size: .8rem; text-transform: uppercase; color: #334e68; }
        .badge { padding: .22rem .55rem; border-radius: 12px; font-size: .78rem; font-weight: 700; }
        .st-Pendiente { background: #fff3e0; color: #e65100; }
        .st-Confirmada { background: #e3f2fd; color: #0d47a1; }
        .st-Completada { background: #e8f5e9; color: #1b5e20; }
        .st-Cancelada { background: #ffebee; color: #b71c1c; }
        .nota-clinica { background: #f8fafc; border-left: 3px solid #2e7d32; padding: .45rem .65rem; font-size: .85rem; }
    </style>
</head>
<body>
<header>
    <div><strong>MedicApp</strong> — Historial de Citas y Expediente Médico</div>
    <nav>
        <a href="<?= htmlspecialchars(url('index.php'), ENT_QUOTES, 'UTF-8') ?>">Volver al Dashboard</a>
        <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>">Cerrar Sesión</a>
    </nav>
</header>

<main class="container">
    <div class="top-bar">
        <h2><?= $rolNombre === 'Paciente' ? 'Mi Historial Médico y Citas' : 'Historial Clínico General' ?></h2>
        <?php if ($rolNombre === 'Paciente'): ?>
            <a class="btn" href="<?= htmlspecialchars(url('citas/agendar.php'), ENT_QUOTES, 'UTF-8') ?>">+ Agendar Nueva Cita</a>
        <?php endif; ?>
    </div>

    <?php if ($mensaje !== ''): ?>
        <div class="alert-ok"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Fecha y Hora</th>
                <?php if ($rolNombre === 'Recepcionista'): ?>
                    <th>Paciente</th>
                <?php endif; ?>
                <th>Especialidad y Médico</th>
                <th>Costo</th>
                <th>Estado</th>
                <th>Motivo y Diagnóstico / Notas Médicas</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($historial)): ?>
            <tr>
                <td colspan="<?= $rolNombre === 'Recepcionista' ? 6 : 5 ?>">No hay registros médicos disponibles.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($historial as $item): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($item['fecha_cita'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                        <?= htmlspecialchars(substr($item['hora_inicio'], 0, 5) . ' - ' . substr($item['hora_fin'], 0, 5), ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <?php if ($rolNombre === 'Recepcionista'): ?>
                        <td><strong><?= htmlspecialchars($item['paciente_nombre'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <?php endif; ?>
                    <td>
                        <strong><?= htmlspecialchars($item['especialidad'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                        <?= htmlspecialchars($item['medico'], ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>$<?= number_format((float)$item['costo_acordado'], 2) ?> MXN</td>
                    <td>
                        <span class="badge st-<?= htmlspecialchars($item['estado'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($item['estado'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td>
                        <strong>Motivo:</strong> <?= htmlspecialchars($item['motivo_consulta'], ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!empty($item['notas_medicas'])): ?>
                            <div class="nota-clinica" style="margin-top:.4rem;">
                                <strong>Diagnóstico / Notas:</strong> <?= htmlspecialchars($item['notas_medicas'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</main>
</body>
</html>