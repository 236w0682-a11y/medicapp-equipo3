<?php
declare(strict_types=1);

/**
 * Envía un correo real autenticándose vía SMTP SSL con Gmail (sin librerías externas).
 */
function enviarViaSmtpGmail(
    string $gmailUser,
    string $gmailAppPass,
    string $emailDestino,
    string $nombreDestino,
    string $asunto,
    string $mensaje
): bool {
    $host = 'ssl://smtp.gmail.com';
    $port = 465;

    $socket = @stream_socket_client("{$host}:{$port}", $errno, $errstr, 15);
    if (!$socket) {
        error_log("Error conexión SMTP Gmail [MedicApp]: {$errstr} ({$errno})");
        return false;
    }

    stream_set_timeout($socket, 15);

    $leerRespuesta = static function ($sock): string {
        $respuesta = '';
        while (($linea = fgets($sock, 515)) !== false) {
            $respuesta .= $linea;
            if (isset($linea[3]) && $linea[3] === ' ') {
                break;
            }
        }
        return $respuesta;
    };

    $enviarComando = static function ($sock, string $comando, string $codigoEsperado) use ($leerRespuesta): bool {
        fwrite($sock, $comando . "\r\n");
        $resp = $leerRespuesta($sock);
        if (!str_starts_with($resp, $codigoEsperado)) {
            error_log("Fallo SMTP Gmail [MedicApp] tras '{$comando}': {$resp}");
            return false;
        }
        return true;
    };

    // 1. Saludo inicial del servidor (220)
    $saludo = $leerRespuesta($socket);
    if (!str_starts_with($saludo, '220')) {
        fclose($socket);
        return false;
    }

    // 2. Handshake y autenticación con App Password
    if (
        !$enviarComando($socket, 'EHLO medicapp.local', '250') ||
        !$enviarComando($socket, 'AUTH LOGIN', '334') ||
        !$enviarComando($socket, base64_encode($gmailUser), '334') ||
        !$enviarComando($socket, base64_encode($gmailAppPass), '235')
    ) {
        fclose($socket);
        return false;
    }

    // 3. Remitente, destinatario e inicio de contenido
    if (
        !$enviarComando($socket, "MAIL FROM:<{$gmailUser}>", '250') ||
        !$enviarComando($socket, "RCPT TO:<{$emailDestino}>", '250') ||
        !$enviarComando($socket, 'DATA', '354')
    ) {
        fclose($socket);
        return false;
    }

    // 4. Encabezados y cuerpo codificados en UTF-8 / Base64
    $asuntoCodificado  = '=?UTF-8?B?' . base64_encode($asunto) . '?=';
    $nombreCodificado  = '=?UTF-8?B?' . base64_encode($nombreDestino) . '?=';
    $cuerpoCodificado  = chunk_split(base64_encode($mensaje));

    $contenido  = "Date: " . date('r') . "\r\n";
    $contenido .= "From: MedicApp Notificaciones <{$gmailUser}>\r\n";
    $contenido .= "Reply-To: {$gmailUser}\r\n";
    $contenido .= "To: {$nombreCodificado} <{$emailDestino}>\r\n";
    $contenido .= "Subject: {$asuntoCodificado}\r\n";
    $contenido .= "MIME-Version: 1.0\r\n";
    $contenido .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $contenido .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $contenido .= $cuerpoCodificado . "\r\n.";

    $enviado = $enviarComando($socket, $contenido, '250');

    fwrite($socket, "QUIT\r\n");
    fclose($socket);

    return $enviado;
}

/**
 * Envía y registra una notificación automática por correo electrónico.
 */
function enviarNotificacionCorreo(
    PDO $pdo,
    string $emailDestino,
    string $nombreDestino,
    string $asunto,
    string $mensaje
): bool {
    $gmailUser    = 'serviciosmedicosmedicapp@gmail.com';
    $gmailAppPass = 'Ltrjvslwaewviyni';

    $enviadoReal = enviarViaSmtpGmail(
        $gmailUser,
        $gmailAppPass,
        $emailDestino,
        $nombreDestino,
        $asunto,
        $mensaje
    );

    // Respaldo y auditoría en base de datos para visualización en el buzón administrativo
    $stmt = $pdo->prepare(
        'INSERT INTO notificaciones_correo (destinatario_email, destinatario_nombre, asunto, mensaje, enviado_smtp)
         VALUES (:email, :nombre, :asunto, :mensaje, :enviado)'
    );
    $stmt->execute([
        ':email'   => $emailDestino,
        ':nombre'  => $nombreDestino,
        ':asunto'  => $asunto,
        ':mensaje' => $mensaje,
        ':enviado' => $enviadoReal ? 1 : 0,
    ]);

    return $enviadoReal;
}