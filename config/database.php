<?php
declare(strict_types=1);

define('DB_HOST', 'sql301.infinityfree.com');
define('DB_NAME', 'if0_43450307_medicapp');
define('DB_USER', 'if0_43450307');
define('DB_PASS', 'Aracely2815FJ');
define('DB_CHARSET', 'utf8mb4');

// Detección automática de carpeta base (ej. /medicapp)
$projectRoot = str_replace('\\', '/', realpath(dirname(__DIR__)) ?: '');
$docRoot     = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
$basePath    = '';

if ($docRoot !== '' && $projectRoot !== '' && str_starts_with($projectRoot, $docRoot)) {
    $basePath = substr($projectRoot, strlen($docRoot));
}

define('BASE_URL', rtrim($basePath, '/'));

$esLocal = in_array($_SERVER['HTTP_HOST'] ?? 'localhost', ['localhost', '127.0.0.1'], true)
    || str_starts_with($_SERVER['HTTP_HOST'] ?? '', 'localhost:');
$protocol = $esLocal ? 'http' : 'https';
$host     = $_SERVER['HTTP_HOST'] ?? 'medicapp-equipo3.infinityfreeapp.com';

define('APP_URL', $protocol . '://' . $host . BASE_URL);

/**
 * Construye una ruta relativa interna (ej. /medicapp/login.php).
 */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Construye la URL absoluta oficial para compartir por correo o enlace externo.
 */
function full_url(string $path = ''): string
{
    return APP_URL . '/' . ltrim($path, '/');
}

/**
 * Redirige de forma segura dentro del proyecto.
 */
function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/**
 * Devuelve una instancia única de PDO con manejo de excepciones.
 */
function getConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Error de conexión BD [MedicApp]: ' . $e->getMessage());
            http_response_code(500);
            exit('Error interno: no fue posible conectar con la base de datos.');
        }
    }

    return $pdo;
}