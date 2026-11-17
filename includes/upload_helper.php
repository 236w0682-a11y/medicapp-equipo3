<?php
declare(strict_types=1);

/**
 * Valida tipo MIME real (JPG/PNG), límite de 2 MB y guarda el archivo con nombre único en uploads/.
 *
 * @return array{ok: bool, filename: ?string, error: ?string}
 */
function procesarImagenServicio(array $file, bool $requerido = true): array
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        if ($requerido) {
            return ['ok' => false, 'filename' => null, 'error' => 'Debe seleccionar una fotografía del médico o especialidad.'];
        }
        return ['ok' => true, 'filename' => null, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'filename' => null, 'error' => 'Error al transferir el archivo al servidor.'];
    }

    $maxBytes = 2 * 1024 * 1024; // 2 MB
    if ($file['size'] > $maxBytes) {
        return ['ok' => false, 'filename' => null, 'error' => 'La imagen supera el límite máximo de 2 MB.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    $mimesPermitidos = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
    ];

    if (!array_key_exists($mimeType, $mimesPermitidos)) {
        return ['ok' => false, 'filename' => null, 'error' => 'Formato inválido. Solo se admiten archivos JPG o PNG.'];
    }

    $uploadDir = dirname(__DIR__) . '/uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $extension = $mimesPermitidos[$mimeType];
    $nombreUnico = 'srv_' . bin2hex(random_bytes(12)) . '.' . $extension;
    $rutaDestino = $uploadDir . '/' . $nombreUnico;

    if (!move_uploaded_file($file['tmp_name'], $rutaDestino)) {
        return ['ok' => false, 'filename' => null, 'error' => 'No se pudo almacenar la imagen en uploads/.'];
    }

    return ['ok' => true, 'filename' => $nombreUnico, 'error' => null];
}

/**
 * Elimina físicamente una imagen previa de uploads/ si existe.
 */
function eliminarImagenServicio(?string $filename): void
{
    if ($filename === null || $filename === '') {
        return;
    }

    $ruta = dirname(__DIR__) . '/uploads/' . basename($filename);
    if (is_file($ruta)) {
        unlink($ruta);
    }
}