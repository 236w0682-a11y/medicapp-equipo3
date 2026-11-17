<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol_nombre'] ?? '') !== 'Paciente') {
    redirect('login.php');
}

$pdo = getConnection();
$pacienteId = (int)$_SESSION['usuario_id'];
$servicioSeleccionado = filter_input(INPUT_GET, 'servicio_id', FILTER_VALIDATE_INT) ?: 0;

$stmtServicios = $pdo->prepare(
    'SELECT id, especialidad, medico, costo, duracion_minutos
     FROM servicios_medicos
     WHERE activo = 1
     ORDER BY especialidad ASC, medico ASC'
);
$stmtServicios->execute();
$servicios = $stmtServicios->fetchAll();

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $servicioId     = filter_input(INPUT_POST, 'servicio_id', FILTER_VALIDATE_INT);
    $fechaCita      = trim($_POST['fecha_cita'] ?? '');
    $horaInicioRaw  = trim($_POST['hora_inicio'] ?? '');
    $motivoConsulta = trim($_POST['motivo_consulta'] ?? '');

    if (!$servicioId) {
        $errores[] = 'Seleccione un servicio médico válido.';
    }
    if ($motivoConsulta === '' || strlen($motivoConsulta) < 5) {
        $errores[] = 'Describa brevemente el motivo de su consulta (mínimo 5 caracteres).';
    }

    $inicioDT = DateTimeImmutable::createFromFormat('Y-m-d H:i', $fechaCita . ' ' . $horaInicioRaw);
    $dtErrors = DateTimeImmutable::getLastErrors();
    if ($inicioDT === false || ($dtErrors && ($dtErrors['warning_count'] > 0 || $dtErrors['error_count'] > 0))) {
        $errores[] = 'Fecha u hora inválida.';
    } else {
        $ahora = new DateTimeImmutable('now');
        if ($inicioDT <= $ahora) {
            $errores[] = 'La cita debe programarse en una fecha y hora futura.';
        }
    }

    if (empty($errores)) {
        try {
            $pdo->beginTransaction();

            $stmtSrv = $pdo->prepare(
                'SELECT id, especialidad, medico, costo, duracion_minutos
                 FROM servicios_medicos
                 WHERE id = :id AND activo = 1
                 LIMIT 1'
            );
            $stmtSrv->execute([':id' => $servicioId]);
            $servicio = $stmtSrv->fetch();

            if (!$servicio) {
                $pdo->rollBack();
                $errores[] = 'El servicio médico seleccionado no está disponible.';
            } else {
                $duracion = (int)$servicio['duracion_minutos'];
                $finDT    = $inicioDT->modify("+{$duracion} minutes");

                $horaInicioSQL = $inicioDT->format('H:i:s');
                $horaFinSQL    = $finDT->format('H:i:s');

                if ($horaInicioSQL < '08:00:00' || $horaFinSQL > '20:00:00' || $finDT->format('Y-m-d') !== $fechaCita) {
                    $pdo->rollBack();
                    $errores[] = 'El horario de atención clínica es de 08:00 a 20:00 hrs.';
                } else {
                    // Verificar traslape en agenda del Médico
                    $sqlTraslapeMedico = "SELECT c.id, c.hora_inicio, c.hora_fin
                                          FROM citas c
                                          INNER JOIN servicios_medicos sm ON c.servicio_id = sm.id
                                          WHERE c.fecha_cita = :fecha
                                            AND sm.medico = :medico
                                            AND c.estado IN ('Pendiente', 'Confirmada')
                                            AND (c.hora_inicio < :hora_fin AND c.hora_fin > :hora_inicio)
                                          LIMIT 1
                                          FOR UPDATE";
                    $stmtCheckMed = $pdo->prepare($sqlTraslapeMedico);
                    $stmtCheckMed->execute([
                        ':fecha'       => $fechaCita,
                        ':medico'      => $servicio['medico'],
                        ':hora_fin'    => $horaFinSQL,
                        ':hora_inicio' => $horaInicioSQL,
                    ]);
                    $citaConflicto = $stmtCheckMed->fetch();

                    // Verificar traslape en citas propias del Paciente
                    $sqlTraslapePac = "SELECT id, hora_inicio, hora_fin
                                       FROM citas
                                       WHERE fecha_cita = :fecha
                                         AND paciente_id = :paciente_id
                                         AND estado IN ('Pendiente', 'Confirmada')
                                         AND (hora_inicio < :hora_fin AND hora_fin > :hora_inicio)
                                       LIMIT 1
                                       FOR UPDATE";
                    $stmtCheckPac = $pdo->prepare($sqlTraslapePac);
                    $stmtCheckPac->execute([
                        ':fecha'       => $fechaCita,
                        ':paciente_id' => $pacienteId,
                        ':hora_fin'    => $horaFinSQL,
                        ':hora_inicio' => $horaInicioSQL,
                    ]);
                    $pacConflicto = $stmtCheckPac->fetch();

                    if ($citaConflicto) {
                        $pdo->rollBack();
                        $errores[] = sprintf(
                            'Horario no disponible: %s ya tiene una cita programada de %s a %s ese día.',
                            $servicio['medico'],
                            substr($citaConflicto['hora_inicio'], 0, 5),
                            substr($citaConflicto['hora_fin'], 0, 5)
                        );
                    } elseif ($pacConflicto) {
                        $pdo->rollBack();
                        $errores[] = sprintf(
                            'Ya tienes otra cita programada de %s a %s en esa misma fecha.',
                            substr($pacConflicto['hora_inicio'], 0, 5),
                            substr($pacConflicto['hora_fin'], 0, 5)
                        );
                    } else {
                        $sqlInsert = "INSERT INTO citas
                                      (paciente_id, servicio_id, fecha_cita, hora_inicio, hora_fin, costo_acordado, estado, motivo_consulta)
                                      VALUES
                                      (:paciente_id, :servicio_id, :fecha_cita, :hora_inicio, :hora_fin, :costo_acordado, 'Pendiente', :motivo)";
                        $stmtIns = $pdo->prepare($sqlInsert);
                        $stmtIns->execute([
                            ':paciente_id'    => $pacienteId,
                            ':servicio_id'    => $servicioId,
                            ':fecha_cita'     => $fechaCita,
                            ':hora_inicio'    => $horaInicioSQL,
                            ':hora_fin'       => $horaFinSQL,
                            ':costo_acordado' => $servicio['costo'],
                            ':motivo'         => $motivoConsulta,
                        ]);

                        $pdo->commit();
                        redirect('citas/historial.php?msg=' . urlencode('Cita agendada correctamente. En espera de confirmación por recepción.'));
                    }
                }
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Error al agendar cita [MedicApp]: ' . $e->getMessage());
            $errores[] = 'Error interno al procesar la solicitud de cita.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>MedicApp — Agendar Cita Médica</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; background: #f0f6fa; color: #1f2933; }
        header { background: #0f4c81; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        header a { color: #fff; text-decoration: none; margin-left: 1rem; font-size: .9rem; }
        .container { max-width: 540px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,.08); }
        h2 { margin-top: 0; color: #0f4c81; }
        label { display: block; margin-top: .95rem; font-weight: 600; font-size: .9rem; }
        input, select, textarea { width: 100%; padding: .6rem; margin-top: .3rem; border: 1px solid #ccd6dd; border-radius: 5px; box-sizing: border-box; font-family: inherit; }
        button { width: 100%; margin-top: 1.3rem; padding: .75rem; background: #0f4c81; color: #fff; border: none; border-radius: 5px; font-weight: 600; cursor: pointer; }
        .alert-err { background: #fdecea; color: #b71c1c; padding: .75rem; border-radius: 5px; margin-bottom: 1rem; }
        .hint { font-size: .82rem; color: #52606d; margin-top: .25rem; }
    </style>
</head>
<body>
<header>
    <div><strong>MedicApp</strong> — Agendamiento de Citas</div>
    <nav>
        <a href="<?= htmlspecialchars(url('index.php'), ENT_QUOTES, 'UTF-8') ?>">Dashboard</a>
        <a href="<?= htmlspecialchars(url('citas/historial.php'), ENT_QUOTES, 'UTF-8') ?>">Mis Citas</a>
        <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>">Salir</a>
    </nav>
</header>

<main class="container">
    <div class="card">
        <h2>Solicitar Cita Médica</h2>

        <?php if (!empty($errores)): ?>
            <div class="alert-err">
                <ul>
                    <?php foreach ($errores as $err): ?>
                        <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= htmlspecialchars(url('citas/agendar.php'), ENT_QUOTES, 'UTF-8') ?>">
            <label for="servicio_id">Servicio / Especialidad Médica</label>
            <select id="servicio_id" name="servicio_id" required>
                <option value="">-- Seleccione especialidad y médico --</option>
                <?php foreach ($servicios as $srv): ?>
                    <?php $sel = (int)($_POST['servicio_id'] ?? $servicioSeleccionado) === (int)$srv['id']; ?>
                    <option value="<?= (int)$srv['id'] ?>" <?= $sel ? 'selected' : '' ?>>
                        <?= htmlspecialchars($srv['especialidad'] . ' — ' . $srv['medico'], ENT_QUOTES, 'UTF-8') ?>
                        ($<?= number_format((float)$srv['costo'], 2) ?> | <?= (int)$srv['duracion_minutos'] ?> min)
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="fecha_cita">Fecha de la Cita</label>
            <input type="date" id="fecha_cita" name="fecha_cita" min="<?= date('Y-m-d') ?>" required
                   value="<?= htmlspecialchars($_POST['fecha_cita'] ?? date('Y-m-d', strtotime('+1 day')), ENT_QUOTES, 'UTF-8') ?>">

            <label for="hora_inicio">Hora de Inicio</label>
            <input type="time" id="hora_inicio" name="hora_inicio" min="08:00" max="19:30" required
                   value="<?= htmlspecialchars($_POST['hora_inicio'] ?? '10:00', ENT_QUOTES, 'UTF-8') ?>">
            <div class="hint">Horario clínico: 08:00 a 20:00 hrs. La hora de término se calcula automáticamente según la duración del servicio.</div>

            <label for="motivo_consulta">Motivo de la Consulta</label>
            <textarea id="motivo_consulta" name="motivo_consulta" rows="3" required
                      placeholder="Describa sus síntomas o el motivo de revisión..."><?= htmlspecialchars($_POST['motivo_consulta'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

            <button type="submit">Verificar Disponibilidad y Agendar</button>
        </form>
    </div>
</main>
</body>
</html>