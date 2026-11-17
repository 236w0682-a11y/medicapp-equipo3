<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol_nombre'] ?? '') !== 'Doctor') {
    redirect('login.php');
}

$pdo           = getConnection();
$usuarioId     = (int)$_SESSION['usuario_id'];
$nombreUsuario = $_SESSION['usuario_nombre'] ?? 'Doctor';

$stmtConfirmadas = $pdo->query("SELECT COUNT(*) FROM citas WHERE estado = 'Confirmada'");
$totalPorAtender = (int)$stmtConfirmadas->fetchColumn();

$stmtConsultas = $pdo->prepare(
    "SELECT COUNT(*) FROM consultas_validacion WHERE destinatario_id = :uid AND (respuesta IS NULL OR respuesta = '')"
);
$stmtConsultas->execute([':uid' => $usuarioId]);
$totalValidaciones = (int)$stmtConsultas->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>MedicApp — Panel Médico (Doctor)</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; background: #f0f6fa; color: #1f2933; }
        header { background: #0f4c81; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .badge { background: #0284c7; padding: .25rem .65rem; border-radius: 12px; font-size: .8rem; margin-left: .5rem; }
        .container { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.2rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,.06); border-top: 4px solid #0284c7; display: flex; flex-direction: column; justify-content: space-between; }
        .card h3 { margin-top: 0; color: #0f4c81; }
        .kpi { font-size: 1.8rem; font-weight: 700; color: #102a43; margin: .5rem 0; }
        .btn { display: inline-block; margin-top: 1rem; padding: .55rem 1rem; background: #0f4c81; color: #fff; text-decoration: none; border-radius: 5px; font-weight: 600; text-align: center; }
        a.logout { color: #fff; text-decoration: none; background: #c62828; padding: .45rem .9rem; border-radius: 5px; font-size: .9rem; }
    </style>
</head>
<body>
<header>
    <div>
        <strong>MedicApp</strong> | Dr(a). <?= htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8') ?>
        <span class="badge">Doctor</span>
    </div>
    <a class="logout" href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>">Cerrar Sesión</a>
</header>

<main class="container">
    <div class="grid">
        <div class="card">
            <div>
                <h3>Consultas de Validación</h3>
                <div class="kpi"><?= $totalValidaciones ?> pendientes</div>
                <p>Confirma la identidad de tus pacientes o personal cuando Gerencia o Sistemas lo soliciten.</p>
            </div>
            <a class="btn" href="<?= htmlspecialchars(url('admin/consultas_registro.php'), ENT_QUOTES, 'UTF-8') ?>">Responder Validaciones</a>
        </div>

        <div class="card">
            <div>
                <h3>Consulta Clínica y Diagnósticos</h3>
                <div class="kpi"><?= $totalPorAtender ?> por atender</div>
                <p>Revisa citas confirmadas, registra diagnósticos y guarda notas médicas en el expediente.</p>
            </div>
            <a class="btn" href="<?= htmlspecialchars(url('citas/gestionar.php'), ENT_QUOTES, 'UTF-8') ?>">Atender Citas</a>
        </div>

        <div class="card">
            <div>
                <h3>Historial Médico General</h3>
                <p>Consulta expedientes previos, motivos de consulta y diagnósticos registrados.</p>
            </div>
            <a class="btn" href="<?= htmlspecialchars(url('citas/historial.php'), ENT_QUOTES, 'UTF-8') ?>">Ver Historial Clínico</a>
        </div>

        <div class="card">
            <div>
                <h3>Especialidades y Servicios</h3>
                <p>Consulta el catálogo vigente de especialidades, médicos asignados y tiempos de consulta.</p>
            </div>
            <a class="btn" href="<?= htmlspecialchars(url('servicios/index.php'), ENT_QUOTES, 'UTF-8') ?>">Ver Catálogo</a>
        </div>
    </div>
</main>
</body>
</html>