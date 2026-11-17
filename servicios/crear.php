<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/upload_helper.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol_nombre'] ?? '') !== 'Recepcionista') {
    redirect('login.php');
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $especialidad = trim($_POST['especialidad'] ?? '');
    $medico       = trim($_POST['medico'] ?? '');
    $costoRaw     = trim($_POST['costo'] ?? '');
    $duracionRaw  = trim($_POST['duracion_minutos'] ?? '');

    $costo    = filter_var($costoRaw, FILTER_VALIDATE_FLOAT);
    $duracion = filter_var($duracionRaw, FILTER_VALIDATE_INT);

    if ($especialidad === '') {
        $errores[] = 'La especialidad es obligatoria.';
    }
    if ($medico === '') {
        $errores[] = 'El nombre del médico es obligatorio.';
    }
    if ($costo === false || $costo <= 0) {
        $errores[] = 'Ingrese un costo válido mayor a 0.';
    }
    if ($duracion === false || $duracion < 10 || $duracion > 240) {
        $errores[] = 'La duración debe estar entre 10 y 240 minutos.';
    }

    $resultadoFoto = ['ok' => false, 'filename' => null, 'error' => null];
    if (empty($errores)) {
        $resultadoFoto = procesarImagenServicio($_FILES['foto'] ?? [], true);
        if (!$resultadoFoto['ok']) {
            $errores[] = $resultadoFoto['error'];
        }
    }

    if (empty($errores)) {
        try {
            $pdo = getConnection();
            $sql = 'INSERT INTO servicios_medicos (especialidad, medico, costo, duracion_minutos, foto)
                    VALUES (:especialidad, :medico, :costo, :duracion_minutos, :foto)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':especialidad'     => $especialidad,
                ':medico'           => $medico,
                ':costo'            => $costo,
                ':duracion_minutos' => $duracion,
                ':foto'             => $resultadoFoto['filename'],
            ]);

            redirect('servicios/index.php?msg=' . urlencode('Servicio médico registrado correctamente.'));
        } catch (PDOException $e) {
            eliminarImagenServicio($resultadoFoto['filename']);
            error_log('Error al crear servicio [MedicApp]: ' . $e->getMessage());
            $errores[] = 'Error interno al guardar el servicio médico.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>MedicApp — Nuevo Servicio Médico</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f4f7f9; display: flex; justify-content: center; padding: 2rem; }
        .card { background: #fff; padding: 2rem; border-radius: 8px; width: 100%; max-width: 480px; box-shadow: 0 2px 10px rgba(0,0,0,.08); }
        h2 { margin-top: 0; color: #0f4c81; }
        label { display: block; margin-top: .9rem; font-weight: 600; font-size: .9rem; }
        input { width: 100%; padding: .6rem; margin-top: .3rem; border: 1px solid #ccd6dd; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; margin-top: 1.3rem; padding: .75rem; background: #0f4c81; color: #fff; border: none; border-radius: 5px; font-weight: 600; cursor: pointer; }
        .alert-err { background: #fdecea; color: #b71c1c; padding: .75rem; border-radius: 5px; margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="card">
    <h2>Registrar Servicio Médico</h2>

    <?php if (!empty($errores)): ?>
        <div class="alert-err">
            <ul>
                <?php foreach ($errores as $err): ?>
                    <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars(url('servicios/crear.php'), ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data">
        <label for="especialidad">Especialidad</label>
        <input type="text" id="especialidad" name="especialidad" required
               value="<?= htmlspecialchars($_POST['especialidad'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <label for="medico">Médico Asignado</label>
        <input type="text" id="medico" name="medico" required
               value="<?= htmlspecialchars($_POST['medico'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <label for="costo">Costo de Consulta (MXN)</label>
        <input type="number" step="0.01" min="1" id="costo" name="costo" required
               value="<?= htmlspecialchars($_POST['costo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <label for="duracion_minutos">Duración (en minutos)</label>
        <input type="number" min="10" max="240" step="5" id="duracion_minutos" name="duracion_minutos" required
               value="<?= htmlspecialchars($_POST['duracion_minutos'] ?? '30', ENT_QUOTES, 'UTF-8') ?>">

        <label for="foto">Foto del Médico / Especialidad (JPG o PNG, máx. 2 MB)</label>
        <input type="file" id="foto" name="foto" accept="image/jpeg,image/png" required>

        <button type="submit">Guardar Servicio</button>
    </form>
    <p style="text-align:center; margin-top:1rem;"><a href="<?= htmlspecialchars(url('servicios/index.php'), ENT_QUOTES, 'UTF-8') ?>">Cancelar y volver</a></p>
</div>
</body>
</html>