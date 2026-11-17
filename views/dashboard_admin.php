<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol_nombre'] ?? '', ['Gerente', 'Sistemas'], true)) {
    redirect('login.php');
}

$pdo           = getConnection();
$nombreUsuario = $_SESSION['usuario_nombre'] ?? 'Administrador';
$rolNombre     = $_SESSION['rol_nombre'];

$totalPendientes = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE estado_aprobacion = 'Pendiente'")->fetchColumn();
$totalActivos    = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE activo = 1")->fetchColumn();
$enlaceOficial   = full_url('login.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>MedicApp — Panel de <?= htmlspecialchars($rolNombre, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; background: #f0f4f8; color: #1f2933; }
        header { background: #102a43; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .badge { background: #0284c7; padding: .25rem .65rem; border-radius: 12px; font-size: .8rem; margin-left: .5rem; }
        .container { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }
        .link-banner { background: #fff; padding: 1rem 1.5rem; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,.06); margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-left: 5px solid #2e7d32; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.2rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,.06); border-top: 4px solid #102a43; display: flex; flex-direction: column; justify-content: space-between; }
        .kpi { font-size: 1.8rem; font-weight: 700; color: #0f4c81; margin: .5rem 0; }
        .btn { display: inline-block; margin-top: 1rem; padding: .55rem 1rem; background: #102a43; color: #fff; text-decoration: none; border-radius: 5px; font-weight: 600; text-align: center; }
        a.logout { color: #fff; text-decoration: none; background: #c62828; padding: .45rem .9rem; border-radius: 5px; font-size: .9rem; }
        code { background: #e2e8f0; padding: .3rem .6rem; border-radius: 4px; font-size: .9rem; }
    </style>
</head>
<body>
<header>
    <div>
        <strong>MedicApp</strong> | <?= htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8') ?>
        <span class="badge"><?= htmlspecialchars($rolNombre, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <a class="logout" href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>">Cerrar Sesión</a>
</header>

<main class="container">
    <div class="link-banner">
        <div>
            <strong>Enlace Oficial de Acceso a MedicApp:</strong><br>
            <code id="officialLink"><?= htmlspecialchars($enlaceOficial, ENT_QUOTES, 'UTF-8') ?></code>
        </div>
        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('officialLink').innerText); alert('Enlace copiado');"
                style="padding:.5rem 1rem; background:#2e7d32; color:#fff; border:none; border-radius:5px; cursor:pointer; font-weight:600;">
            Copiar Enlace
        </button>
    </div>

    <div class="grid">
        <div class="card">
            <div>
                <h3>Aprobación de Registros</h3>
                <div class="kpi"><?= $totalPendientes ?> pendientes</div>
                <p>Valida identidades, consulta con doctores/recepción y aprueba o rechaza solicitudes.</p>
            </div>
            <a class="btn" href="<?= htmlspecialchars(url('admin/aprobaciones.php'), ENT_QUOTES, 'UTF-8') ?>">Gestionar Solicitudes</a>
        </div>

        <div class="card">
            <div>
                <h3>Buzón de Notificaciones</h3>
                <div class="kpi"><?= $totalActivos ?> usuarios activos</div>
                <p>Revisa los correos enviados automáticamente con credenciales y enlace oficial.</p>
            </div>
            <a class="btn" href="<?= htmlspecialchars(url('admin/buzon_correos.php'), ENT_QUOTES, 'UTF-8') ?>">Ver Correos Enviados</a>
        </div>

        <div class="card">
            <div>
                <h3>Supervisión Clínica</h3>
                <p>Consulta el catálogo de servicios médicos y el historial general de citas.</p>
            </div>
            <a class="btn" href="<?= htmlspecialchars(url('citas/historial.php'), ENT_QUOTES, 'UTF-8') ?>">Ver Historial General</a>
        </div>
    </div>
</main>
</body>
</html>