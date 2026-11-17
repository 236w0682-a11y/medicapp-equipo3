<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/mail_helper.php';

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol_nombre'] ?? '', ['Gerente', 'Sistemas'], true)) {
    redirect('login.php');
}

$pdo         = getConnection();
$adminId     = (int)$_SESSION['usuario_id'];
$adminNombre = $_SESSION['usuario_nombre'] ?? 'Administrador';
$adminRol    = $_SESSION['rol_nombre'];

$mensaje = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // 1. Aprobar o Rechazar registro
    if ($accion === 'aprobar' || $accion === 'rechazar') {
        $targetId = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);
        $motivo   = trim($_POST['motivo_rechazo'] ?? '');

        if (!$targetId) {
            $error = 'Usuario inválido.';
        } else {
            try {
                $stmtUser = $pdo->prepare(
                    'SELECT u.id, u.nombre, u.apellido, u.email, r.nombre AS rol_nombre
                     FROM usuarios u
                     INNER JOIN roles r ON u.rol_id = r.id
                     WHERE u.id = :id AND u.estado_aprobacion = \'Pendiente\'
                     LIMIT 1'
                );
                $stmtUser->execute([':id' => $targetId]);
                $solicitante = $stmtUser->fetch();

                if (!$solicitante) {
                    $error = 'La solicitud ya fue procesada o no existe.';
                } else {
                    $nombreCompleto = $solicitante['nombre'] . ' ' . $solicitante['apellido'];
                    $linkOficial    = full_url('login.php');

                    if ($accion === 'aprobar') {
                        $stmtUpd = $pdo->prepare(
                            "UPDATE usuarios
                             SET estado_aprobacion = 'Aprobado', activo = 1, aprobado_por = :admin_id, motivo_rechazo = NULL
                             WHERE id = :id"
                        );
                        $stmtUpd->execute([':admin_id' => $adminId, ':id' => $targetId]);

                        $asunto = 'MedicApp — Registro Aprobado';
                        $cuerpo = "Hola {$nombreCompleto},\n\n"
                            . "¡Tu identidad ha sido validada con éxito!\n"
                            . "Fuiste inscrito como {$solicitante['rol_nombre']} en MedicApp.\n\n"
                            . "Ya puedes ingresar al sistema desde nuestro enlace oficial:\n"
                            . "{$linkOficial}\n\n"
                            . "Aprobado por: {$adminNombre} ({$adminRol})";

                        enviarNotificacionCorreo($pdo, $solicitante['email'], $nombreCompleto, $asunto, $cuerpo);
                        $mensaje = "Registro aprobado. Se notificó automáticamente a {$nombreCompleto} que fue inscrito como {$solicitante['rol_nombre']}.";
                    } else {
                        $stmtUpd = $pdo->prepare(
                            "UPDATE usuarios
                             SET estado_aprobacion = 'Rechazado', activo = 0, aprobado_por = :admin_id, motivo_rechazo = :motivo
                             WHERE id = :id"
                        );
                        $stmtUpd->execute([
                            ':admin_id' => $adminId,
                            ':motivo'   => $motivo !== '' ? $motivo : 'No fue posible validar la identidad.',
                            ':id'       => $targetId,
                        ]);

                        $asunto = 'MedicApp — Solicitud de Registro Rechazada';
                        $cuerpo = "Hola {$nombreCompleto},\n\n"
                            . "Te informamos que tu solicitud de registro como {$solicitante['rol_nombre']} en MedicApp fue rechazada.\n"
                            . ($motivo !== '' ? "Motivo: {$motivo}\n\n" : "\n")
                            . "Si consideras que se trata de un error, comunícate con recepción o administración.";

                        enviarNotificacionCorreo($pdo, $solicitante['email'], $nombreCompleto, $asunto, $cuerpo);
                        $mensaje = "Solicitud rechazada y notificación enviada a {$nombreCompleto}.";
                    }
                }
            } catch (PDOException $e) {
                error_log('Error al procesar aprobación [MedicApp]: ' . $e->getMessage());
                $error = 'Error interno al actualizar el registro.';
            }
        }
    }

    // 2. Enviar mensaje interno a Doctor o Recepcionista para pedir validación
    if ($accion === 'consultar_personal') {
        $pendienteId    = filter_input(INPUT_POST, 'usuario_pendiente_id', FILTER_VALIDATE_INT);
        $destinatarioId = filter_input(INPUT_POST, 'destinatario_id', FILTER_VALIDATE_INT);
        $textoConsulta  = trim($_POST['mensaje_consulta'] ?? '');

        if (!$pendienteId || !$destinatarioId || $textoConsulta === '') {
            $error = 'Seleccione un Doctor o Recepcionista y escriba el mensaje de consulta.';
        } else {
            try {
                $stmtIns = $pdo->prepare(
                    'INSERT INTO consultas_validacion (usuario_pendiente_id, remitente_id, destinatario_id, mensaje)
                     VALUES (:pid, :rem, :dest, :msg)'
                );
                $stmtIns->execute([
                    ':pid'  => $pendienteId,
                    ':rem'  => $adminId,
                    ':dest' => $destinatarioId,
                    ':msg'  => $textoConsulta,
                ]);
                $mensaje = 'Consulta enviada al personal médico/recepción para validar el registro.';
            } catch (PDOException $e) {
                error_log('Error al enviar consulta interna [MedicApp]: ' . $e->getMessage());
                $error = 'No se pudo enviar el mensaje interno.';
            }
        }
    }
}

// Listar solicitudes pendientes
$stmtPendientes = $pdo->query(
    "SELECT u.id, u.nombre, u.apellido, u.email, u.telefono, u.created_at, r.nombre AS rol_nombre
     FROM usuarios u
     INNER JOIN roles r ON u.rol_id = r.id
     WHERE u.estado_aprobacion = 'Pendiente'
     ORDER BY u.created_at ASC"
);
$pendientes = $stmtPendientes->fetchAll();

// Listar Doctores y Recepcionistas activos para comunicación interna
$stmtPersonal = $pdo->query(
    "SELECT u.id, u.nombre, u.apellido, r.nombre AS rol_nombre
     FROM usuarios u
     INNER JOIN roles r ON u.rol_id = r.id
     WHERE r.nombre IN ('Doctor', 'Recepcionista') AND u.activo = 1
     ORDER BY r.nombre ASC, u.nombre ASC"
);
$personalClinico = $stmtPersonal->fetchAll();

// Listar hilos de consultas internas
$stmtConsultas = $pdo->query(
    "SELECT cv.id, cv.usuario_pendiente_id, cv.mensaje, cv.respuesta, cv.respondido_at, cv.created_at,
            CONCAT(rem.nombre, ' ', rem.apellido) AS remitente_nombre,
            CONCAT(dest.nombre, ' ', dest.apellido) AS destinatario_nombre,
            rdest.nombre AS destinatario_rol
     FROM consultas_validacion cv
     INNER JOIN usuarios rem ON cv.remitente_id = rem.id
     INNER JOIN usuarios dest ON cv.destinatario_id = dest.id
     INNER JOIN roles rdest ON dest.rol_id = rdest.id
     ORDER BY cv.created_at DESC"
);
$consultasPorUsuario = [];
foreach ($stmtConsultas->fetchAll() as $c) {
    $consultasPorUsuario[(int)$c['usuario_pendiente_id']][] = $c;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>MedicApp — Aprobación de Registros</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; background: #f4f7f9; color: #1f2933; }
        header { background: #102a43; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        header a { color: #fff; text-decoration: none; margin-left: 1rem; font-size: .9rem; }
        .container { max-width: 1150px; margin: 2rem auto; padding: 0 1rem; }
        .alert-ok { background: #e8f5e9; color: #1b5e20; padding: .8rem 1rem; border-radius: 5px; margin-bottom: 1rem; }
        .alert-err { background: #fdecea; color: #b71c1c; padding: .8rem 1rem; border-radius: 5px; margin-bottom: 1rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 1.5rem; border-left: 5px solid #0f4c81; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1rem; }
        .badge { background: #fff3e0; color: #e65100; padding: .2rem .6rem; border-radius: 12px; font-size: .8rem; font-weight: 700; }
        input[type="text"], select { width: 100%; padding: .5rem; border: 1px solid #ccd6dd; border-radius: 4px; box-sizing: border-box; margin-top: .35rem; }
        .btn { padding: .5rem .9rem; border: none; border-radius: 4px; color: #fff; font-weight: 600; cursor: pointer; font-size: .85rem; margin-top: .5rem; }
        .btn-approve { background: #2e7d32; }
        .btn-reject { background: #c62828; }
        .btn-msg { background: #0f4c81; }
        .hilo { background: #f8fafc; border: 1px solid #d9e2ec; padding: .6rem; border-radius: 5px; margin-top: .5rem; font-size: .85rem; }
        .resp { color: #1b5e20; margin-top: .35rem; padding-top: .35rem; border-top: 1px dashed #cbd2d9; }
    </style>
</head>
<body>
<header>
    <div><strong>MedicApp</strong> — Validación de Registros (<?= htmlspecialchars($adminRol, ENT_QUOTES, 'UTF-8') ?>)</div>
    <nav>
        <a href="<?= htmlspecialchars(url('index.php'), ENT_QUOTES, 'UTF-8') ?>">Dashboard</a>
        <a href="<?= htmlspecialchars(url('admin/buzon_correos.php'), ENT_QUOTES, 'UTF-8') ?>">Correos Enviados</a>
        <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>">Cerrar Sesión</a>
    </nav>
</header>

<main class="container">
    <h2>Solicitudes de Registro Pendientes (<?= count($pendientes) ?>)</h2>

    <?php if ($mensaje !== ''): ?>
        <div class="alert-ok"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert-err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (empty($pendientes)): ?>
        <div class="card">No hay solicitudes pendientes de aprobación en este momento.</div>
    <?php else: ?>
        <?php foreach ($pendientes as $p): ?>
            <?php $uid = (int)$p['id']; ?>
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <h3 style="margin:0;"><?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <small>
                            Correo: <strong><?= htmlspecialchars($p['email'], ENT_QUOTES, 'UTF-8') ?></strong> |
                            Tel: <?= htmlspecialchars($p['telefono'] ?? 'N/D', ENT_QUOTES, 'UTF-8') ?> |
                            Fecha: <?= htmlspecialchars($p['created_at'], ENT_QUOTES, 'UTF-8') ?>
                        </small>
                    </div>
                    <span class="badge">Rol solicitado: <?= htmlspecialchars($p['rol_nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <div class="grid-2">
                    <!-- Columna 1: Decisión de Aprobación / Rechazo -->
                    <div>
                        <strong>1. Decisión de Identidad:</strong>
                        <form method="POST" action="<?= htmlspecialchars(url('admin/aprobaciones.php'), ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="usuario_id" value="<?= $uid ?>">
                            <input type="hidden" name="accion" value="aprobar">
                            <button type="submit" class="btn btn-approve">Aprobar e Inscribir como <?= htmlspecialchars($p['rol_nombre'], ENT_QUOTES, 'UTF-8') ?></button>
                        </form>

                        <form method="POST" action="<?= htmlspecialchars(url('admin/aprobaciones.php'), ENT_QUOTES, 'UTF-8') ?>" style="margin-top:.6rem;">
                            <input type="hidden" name="usuario_id" value="<?= $uid ?>">
                            <input type="hidden" name="accion" value="rechazar">
                            <input type="text" name="motivo_rechazo" placeholder="Motivo de rechazo (opcional)...">
                            <button type="submit" class="btn btn-reject">Rechazar Solicitud</button>
                        </form>
                    </div>

                    <!-- Columna 2: Comunicación con Doctor o Recepcionista -->
                    <div>
                        <strong>2. Consultar a Doctor o Recepcionista:</strong>
                        <form method="POST" action="<?= htmlspecialchars(url('admin/aprobaciones.php'), ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="accion" value="consultar_personal">
                            <input type="hidden" name="usuario_pendiente_id" value="<?= $uid ?>">
                            <select name="destinatario_id" required>
                                <option value="">-- Elegir Doctor o Recepcionista --</option>
                                <?php foreach ($personalClinico as $pc): ?>
                                    <option value="<?= (int)$pc['id'] ?>">
                                        [<?= htmlspecialchars($pc['rol_nombre'], ENT_QUOTES, 'UTF-8') ?>] <?= htmlspecialchars($pc['nombre'] . ' ' . $pc['apellido'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="mensaje_consulta" required placeholder="Ej. ¿Confirmas que es tu paciente para aceptar su registro?">
                            <button type="submit" class="btn btn-msg">Enviar Consulta Interna</button>
                        </form>

                        <?php if (!empty($consultasPorUsuario[$uid])): ?>
                            <?php foreach ($consultasPorUsuario[$uid] as $hilo): ?>
                                <div class="hilo">
                                    <strong>Para <?= htmlspecialchars($hilo['destinatario_nombre'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($hilo['destinatario_rol'], ENT_QUOTES, 'UTF-8') ?>):</strong>
                                    <?= htmlspecialchars($hilo['mensaje'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php if (!empty($hilo['respuesta'])): ?>
                                        <div class="resp">
                                            <strong>Respuesta:</strong> <?= htmlspecialchars($hilo['respuesta'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="color:#627d98; margin-top:.25rem;"><em>Esperando respuesta del personal...</em></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
</body>
</html>