<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['rol_nombre'])) {
    redirect('login.php');
}

$pdo = getConnection();
$esRecepcionista = ($_SESSION['rol_nombre'] === 'Recepcionista');
$esPaciente      = ($_SESSION['rol_nombre'] === 'Paciente');

$stmt = $pdo->prepare(
    'SELECT id, especialidad, medico, costo, duracion_minutos, foto
     FROM servicios_medicos
     WHERE activo = :activo
     ORDER BY especialidad ASC, medico ASC'
);
$stmt->execute([':activo' => 1]);
$servicios = $stmt->fetchAll();

$mensaje = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>MedicApp — Servicios Médicos</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; background: #f4f7f9; color: #1f2933; }
        header { background: #0f4c81; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        header a { color: #fff; text-decoration: none; margin-left: 1rem; font-size: .9rem; }
        .container { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem; }
        .btn { display: inline-block; padding: .55rem 1rem; border-radius: 5px; text-decoration: none; font-weight: 600; font-size: .9rem; border: none; cursor: pointer; }
        .btn-primary { background: #0f4c81; color: #fff; }
        .btn-success { background: #2e7d32; color: #fff; padding: .4rem .75rem; font-size: .82rem; }
        .btn-warn { background: #ef6c00; color: #fff; padding: .35rem .7rem; font-size: .82rem; }
        .btn-danger { background: #c62828; color: #fff; padding: .35rem .7rem; font-size: .82rem; }
        .alert-ok { background: #e8f5e9; color: #1b5e20; padding: .75rem 1rem; border-radius: 5px; margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        th, td { padding: .85rem 1rem; text-align: left; border-bottom: 1px solid #e5e9f0; vertical-align: middle; }
        th { background: #e9eff5; font-size: .85rem; text-transform: uppercase; color: #334e68; }
        .thumb { width: 64px; height: 64px; object-fit: cover; border-radius: 6px; border: 1px solid #d9e2ec; }
        .no-thumb { width: 64px; height: 64px; background: #d9e2ec; color: #486581; display: flex; align-items: center; justify-content: center; border-radius: 6px; font-size: .72rem; text-align: center; }
        .acciones { display: flex; gap: .4rem; }
    </style>
</head>
<body>
<header>
    <div><strong>MedicApp</strong> — Catálogo de Servicios Médicos</div>
    <nav>
        <a href="<?= htmlspecialchars(url('index.php'), ENT_QUOTES, 'UTF-8') ?>">Volver al Dashboard</a>
        <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>">Cerrar Sesión</a>
    </nav>
</header>

<main class="container">
    <div class="top-bar">
        <h2>Servicios Médicos Disponibles</h2>
        <?php if ($esRecepcionista): ?>
            <a class="btn btn-primary" href="<?= htmlspecialchars(url('servicios/crear.php'), ENT_QUOTES, 'UTF-8') ?>">+ Nuevo Servicio Médico</a>
        <?php endif; ?>
    </div>

    <?php if ($mensaje !== ''): ?>
        <div class="alert-ok"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Foto</th>
                <th>Especialidad</th>
                <th>Médico</th>
                <th>Costo</th>
                <th>Duración</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($servicios)): ?>
            <tr>
                <td colspan="6">No hay servicios médicos registrados.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($servicios as $srv): ?>
                <tr>
                    <td>
                        <?php if (!empty($srv['foto']) && is_file(__DIR__ . '/../uploads/' . $srv['foto'])): ?>
                            <img class="thumb"
                                 src="<?= htmlspecialchars(url('uploads/' . $srv['foto']), ENT_QUOTES, 'UTF-8') ?>"
                                 alt="Foto de <?= htmlspecialchars($srv['medico'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php else: ?>
                            <div class="no-thumb">Sin foto</div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= htmlspecialchars($srv['especialidad'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars($srv['medico'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>$<?= number_format((float)$srv['costo'], 2) ?> MXN</td>
                    <td><?= (int)$srv['duracion_minutos'] ?> min</td>
                    <td>
                        <?php if ($esRecepcionista): ?>
                            <div class="acciones">
                                <a class="btn btn-warn" href="<?= htmlspecialchars(url('servicios/editar.php?id=' . (int)$srv['id']), ENT_QUOTES, 'UTF-8') ?>">Editar</a>
                                <form method="POST" action="<?= htmlspecialchars(url('servicios/eliminar.php'), ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('¿Eliminar este servicio médico?');">
                                    <input type="hidden" name="id" value="<?= (int)$srv['id'] ?>">
                                    <button type="submit" class="btn btn-danger">Eliminar</button>
                                </form>
                            </div>
                        <?php elseif ($esPaciente): ?>
                            <a class="btn btn-success" href="<?= htmlspecialchars(url('citas/agendar.php?servicio_id=' . (int)$srv['id']), ENT_QUOTES, 'UTF-8') ?>">Agendar Cita</a>
                        <?php else: ?>
                            <span style="color:#52606d; font-size:.85rem;">Solo lectura</span>
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