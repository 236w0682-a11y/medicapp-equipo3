<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol_nombre'] ?? '') !== 'Paciente') {
    redirect('login.php');
}

$pdo           = getConnection();
$usuarioId     = (int)$_SESSION['usuario_id'];
$nombreUsuario = $_SESSION['usuario_nombre'] ?? 'Paciente';

$stmtProximas = $pdo->prepare(
    "SELECT COUNT(*) FROM citas WHERE paciente_id = :pid AND estado IN ('Pendiente', 'Confirmada')"
);
$stmtProximas->execute([':pid' => $usuarioId]);
$totalProximas = (int)$stmtProximas->fetchColumn();

$stmtHistorial = $pdo->prepare(
    "SELECT COUNT(*) FROM citas WHERE paciente_id = :pid AND estado = 'Completada'"
);
$stmtHistorial->execute([':pid' => $usuarioId]);
$totalCompletadas = (int)$stmtHistorial->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>MedicApp — Portal del Paciente</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; background: #f0f6fa; color: #1f2933; }
        header { background: #0f4c81; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .badge { background: #2e7d32; padding: .25rem .6rem; border-radius: 12px; font-size: .8rem; margin-left: .5rem; }
        .container { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.2rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,.06); border-top: 4px solid #0f4c81; display: flex; flex-direction: column; justify-content: space-between; }
        .card h3 { margin-top: 0; color: #0f4c81; }
        .kpi { font-size: 1.8rem; font-weight: 700; color: #102a43; margin: .5rem 0; }
        .btn { display: inline-block; margin-top: 1rem; padding: .55rem 1rem; background: #0f4c81; color: #fff; text-decoration: none; border-radius: 5px; font-weight: 600; text-align: center; }
        a.logout { color: #fff; text-decoration: none; background: #c62828; padding: .45rem .9rem; border-radius: 5px; font-size: .9rem; }
    </style>
</head>
<body>
<header>
    <div>
        <strong>MedicApp</strong> | Bienvenido, <?= htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8') ?>
        <span class="badge">Paciente</span>
    </div>
    <a class="logout" href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>">Cerrar Sesión</a>
</header>

<main class="container">
    <div class="grid">
        <div class="card">
            <div>
                <h3>Servicios Médicos</h3>
                <p>Consulta especialidades disponibles, médicos asignados, costos y duración de consulta.</p>
            </div>
            <a class="btn" href="<?= htmlspecialchars(url('servicios/index.php'), ENT_QUOTES, 'UTF-8') ?>">Ver Catálogo de Servicios</a>
        </div>

        <div class="card">
            <div>
                <h3>Agendar Cita Médica</h3>
                <div class="kpi"><?= $totalProximas ?> activas</div>
                <p>Reserva fecha y horario con validación automática de disponibilidad sin traslapes.</p>
            </div>
            <a class="btn" href="<?= htmlspecialchars(url('citas/agendar.php'), ENT_QUOTES, 'UTF-8') ?>">Agendar Nueva Cita</a>
        </div>

        <div class="card">
            <div>
                <h3>Historial Médico y Citas</h3>
                <div class="kpi"><?= $totalCompletadas ?> consultas</div>
                <p>Revisa el estado de tus citas programadas, diagnósticos y notas médicas.</p>
            </div>
            <a class="btn" href="<?= htmlspecialchars(url('citas/historial.php'), ENT_QUOTES, 'UTF-8') ?>">Consultar Mi Historial</a>
        </div>
    </div>
</main>
</body>
</html>