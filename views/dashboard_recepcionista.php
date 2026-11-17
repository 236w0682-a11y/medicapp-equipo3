<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol_nombre'] ?? '') !== 'Recepcionista') {
    redirect('login.php');
}

$pdo           = getConnection();
$nombreUsuario = $_SESSION['usuario_nombre'] ?? 'Recepcionista';

$stmtPendientes = $pdo->query("SELECT COUNT(*) FROM citas WHERE estado = 'Pendiente'");
$totalPendientes = (int)$stmtPendientes->fetchColumn();

$stmtServicios = $pdo->query("SELECT COUNT(*) FROM servicios_medicos WHERE activo = 1");
$totalServicios = (int)$stmtServicios->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>MedicApp — Panel de Recepción</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; background: #f5f5f7; color: #1f2933; }
        header { background: #1b365d; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .badge { background: #ef6c00; padding: .25rem .6rem; border-radius: 12px; font-size: .8rem; margin-left: .5rem; }
        .container { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.2rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,.06); border-top: 4px solid #ef6c00; display: flex; flex-direction: column; justify-content: space-between; }
        .card h3 { margin-top: 0; color: #1b365d; }
        .kpi { font-size: 1.8rem; font-weight: 700; color: #102a43; margin: .5rem 0; }
        .btn { display: inline-block; margin-top: 1rem; padding: .55rem 1rem; background: #1b365d; color: #fff; text-decoration: none; border-radius: 5px; font-weight: 600; text-align: center; }
        a.logout { color: #fff; text-decoration: none; background: #c62828; padding: .45rem .9rem; border-radius: 5px; font-size: .9rem; }
    </style>
</head>
<body>
<header>
    <div>
        <strong>MedicApp</strong> | Panel Administrativo — <?= htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8') ?>
        <span class="badge">Recepcionista</span>
    </div>
    <a class="logout" href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>">Cerrar Sesión</a>
</header>

<main class="container">
    <div class="grid">
        <div class="card">
            <div>
                <h3>Consultas de Gerencia / Sistemas</h3>
                <p>Responde solicitudes de validación de pacientes o personal antes de aprobar su registro.</p>
            </div>
            <a class="btn" href="<?= htmlspecialchars(url('admin/consultas_registro.php'), ENT_QUOTES, 'UTF-8') ?>">Responder Validaciones</a>
        </div>
        <div class="card">
            <div>
                <h3>Catálogo de Servicios Médicos</h3>
                <div class="kpi"><?= $totalServicios ?> activos</div>
                <p>Administra especialidades, médicos, tarifas, duración y fotografías (CRUD completo).</p>
            </div>
            <a class="btn" href="<?= htmlspecialchars(url('servicios/index.php'), ENT_QUOTES, 'UTF-8') ?>">Gestionar Servicios</a>
        </div>

        <div class="card">
            <div>
                <h3>Gestión y Confirmación de Citas</h3>
                <div class="kpi"><?= $totalPendientes ?> pendientes</div>
                <p>Confirma solicitudes de citas, registra atención médica y actualiza estados.</p>
            </div>
            <a class="btn" href="<?= htmlspecialchars(url('citas/gestionar.php'), ENT_QUOTES, 'UTF-8') ?>">Administrar Agenda</a>
        </div>

        <div class="card">
            <div>
                <h3>Historial Médico General</h3>
                <p>Consulta el expediente histórico de consultas atendidas y notas clínicas registradas.</p>
            </div>
            <a class="btn" href="<?= htmlspecialchars(url('citas/historial.php'), ENT_QUOTES, 'UTF-8') ?>">Ver Historial Clínico</a>
        </div>
    </div>
</main>
</body>
</html>