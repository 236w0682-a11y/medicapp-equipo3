<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol_nombre'] ?? '', ['Recepcionista', 'Doctor'], true)) {
    redirect('login.php');
}

$pdo = getConnection();
$mensaje = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $citaId      = filter_input(INPUT_POST, 'cita_id', FILTER_VALIDATE_INT);
    $nuevoEstado = $_POST['nuevo_estado'] ?? '';
    $notas       = trim($_POST['notas_medicas'] ?? '');

    $estadosPermitidos = ['Confirmada', 'Completada', 'Cancelada'];

    if (!$citaId || !in_array($nuevoEstado, $estadosPermitidos, true)) {
        $error = 'Solicitud inválida para actualizar el estado de la cita.';
    } elseif ($nuevoEstado === 'Completada' && $notas === '') {
        $error = 'Para marcar la cita como Completada debe ingresar el diagnóstico o notas médicas para el historial.';
    } else {
        try {
            $sql = 'UPDATE citas
                    SET estado = :estado,
                        notas_medicas = CASE WHEN :notas_check <> \'\' THEN :notas_val ELSE notas_medicas END
                    WHERE id = :id';
            $stmtUpd = $pdo->prepare($sql);
            $stmtUpd->execute([
                ':estado'      => $nuevoEstado,
                ':notas_check' => $notas,
                ':notas_val'   => $notas,
                ':id'          => $citaId,
            ]);

            $mensaje = "Cita #{$citaId} actualizada a estado: {$nuevoEstado}.";
        } catch (PDOException $e) {
            error_log('Error al gestionar cita [MedicApp]: ' . $e->getMessage());
            $error = 'Error interno al actualizar la cita.';
        }
    }
}

$sqlCitas = "SELECT c.id, c.fecha_cita, c.hora_inicio, c.hora_fin, c.costo_acordado,
                    c.estado, c.motivo_consulta, c.notas_medicas,
                    CONCAT(u.nombre, ' ', u.apellido) AS paciente_nombre,
                    u.telefono AS paciente_telefono,
                    sm.especialidad, sm.medico
             FROM citas c
             INNER JOIN usuarios u ON c.paciente_id = u.id
             INNER JOIN servicios_medicos sm ON c.servicio_id = sm.id
             ORDER BY FIELD(c.estado, 'Pendiente', 'Confirmada', 'Completada', 'Cancelada'),
                      c.fecha_cita ASC, c.hora_inicio ASC";
$stmt = $pdo->prepare($sqlCitas);
$stmt->execute();
$citas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>MedicApp — Gestión y Atención de Citas</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; background: #f5f5f7; color: #1f2933; }
        header { background: #1b365d; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        header a { color: #fff; text-decoration: none; margin-left: 1rem; font-size: .9rem; }
        .container { max-width: 1180px; margin: 2rem auto; padding: 0 1rem; }
        .alert-ok { background: #e8f5e9; color: #1b5e20; padding: .75rem 1rem; border-radius: 5px; margin-bottom: 1rem; }
        .alert-err { background: #fdecea; color: #b71c1c; padding: .75rem 1rem; border-radius: 5px; margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        th, td { padding: .8rem; text-align: left; border-bottom: 1px solid #e5e9f0; vertical-align: top; font-size: .9rem; }
        th { background: #e9eff5; font-size: .8rem; text-transform: uppercase; color: #334e68; }
        .badge { padding: .22rem .55rem; border-radius: 12px; font-size: .78rem; font-weight: 700; display: inline-block; }
        .st-Pendiente { background: #fff3e0; color: #e65100; }
        .st-Confirmada { background: #e3f2fd; color: #0d47a1; }
        .st-Completada { background: #e8f5e9; color: #1b5e20; }
        .st-Cancelada { background: #ffebee; color: #b71c1c; }
        .btn { padding: .4rem .7rem; border: none; border-radius: 4px; color: #fff; font-weight: 600; cursor: pointer; font-size: .8rem; margin-top: .3rem; }
        .btn-confirm { background: #0f4c81; }
        .btn-complete { background: #2e7d32; }
        .btn-cancel { background: #c62828; }
        textarea { width: 100%; font-size: .82rem; padding: .35rem; border: 1px solid #ccd6dd; border-radius: 4px; box-sizing: border-box; margin-top: .3rem; }
    </style>
</head>
<body>
<header>
    <div><strong>MedicApp</strong> — Agenda y Atención Clínica</div>
    <nav>
        <a href="<?= htmlspecialchars(url('index.php'), ENT_QUOTES, 'UTF-8') ?>">Dashboard</a>
        <a href="<?= htmlspecialchars(url('citas/historial.php'), ENT_QUOTES, 'UTF-8') ?>">Historial Médico</a>
        <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>">Salir</a>
    </nav>
</header>

<main class="container">
    <h2>Administración de Solicitudes y Citas</h2>

    <?php if ($mensaje !== ''): ?>
        <div class="alert-ok"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert-err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Fecha y Horario</th>
                <th>Paciente</th>
                <th>Servicio / Médico</th>
                <th>Motivo</th>
                <th>Estado</th>
                <th>Acciones / Notas Médicas</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($citas)): ?>
            <tr><td colspan="6">No existen citas registradas.</td></tr>
        <?php else: ?>
            <?php foreach ($citas as $c): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($c['fecha_cita'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                        <?= htmlspecialchars(substr($c['hora_inicio'], 0, 5) . ' - ' . substr($c['hora_fin'], 0, 5), ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($c['paciente_nombre'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                        <small>Tel: <?= htmlspecialchars($c['paciente_telefono'] ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></small>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($c['especialidad'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                        <?= htmlspecialchars($c['medico'], ENT_QUOTES, 'UTF-8') ?><br>
                        <small>$<?= number_format((float)$c['costo_acordado'], 2) ?> MXN</small>
                    </td>
                    <td><?= htmlspecialchars($c['motivo_consulta'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <span class="badge st-<?= htmlspecialchars($c['estado'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($c['estado'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td style="min-width: 240px;">
                        <?php if ($c['estado'] === 'Pendiente'): ?>
                            <form method="POST" action="<?= htmlspecialchars(url('citas/gestionar.php'), ENT_QUOTES, 'UTF-8') ?>" style="display:inline;">
                                <input type="hidden" name="cita_id" value="<?= (int)$c['id'] ?>">
                                <input type="hidden" name="nuevo_estado" value="Confirmada">
                                <button type="submit" class="btn btn-confirm">Confirmar Cita</button>
                            </form>
                            <form method="POST" action="<?= htmlspecialchars(url('citas/gestionar.php'), ENT_QUOTES, 'UTF-8') ?>" style="display:inline;" onsubmit="return confirm('¿Cancelar esta cita?');">
                                <input type="hidden" name="cita_id" value="<?= (int)$c['id'] ?>">
                                <input type="hidden" name="nuevo_estado" value="Cancelada">
                                <button type="submit" class="btn btn-cancel">Cancelar</button>
                            </form>
                        <?php elseif ($c['estado'] === 'Confirmada'): ?>
                            <form method="POST" action="<?= htmlspecialchars(url('citas/gestionar.php'), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="cita_id" value="<?= (int)$c['id'] ?>">
                                <input type="hidden" name="nuevo_estado" value="Completada">
                                <textarea name="notas_medicas" rows="2" required placeholder="Diagnóstico, tratamiento o notas clínicas..."></textarea>
                                <button type="submit" class="btn btn-complete">Finalizar y Guardar en Historial</button>
                            </form>
                        <?php else: ?>
                            <small><?= htmlspecialchars($c['notas_medicas'] ?? 'Sin notas adicionales.', ENT_QUOTES, 'UTF-8') ?></small>
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