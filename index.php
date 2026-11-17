<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/config/database.php';

// Si no ha iniciado sesión, mostrar el portal oficial de bienvenida (inicio.php)
if (!isset($_SESSION['usuario_id'], $_SESSION['rol_nombre'])) {
    require __DIR__ . '/inicio.php';
    exit;
}

$pdo           = getConnection();
$usuarioId     = (int)$_SESSION['usuario_id'];
$nombreUsuario = $_SESSION['usuario_nombre'] ?? 'Usuario';
$rolNombre     = $_SESSION['rol_nombre'];

switch ($rolNombre) {
    case 'Paciente':
        require __DIR__ . '/views/dashboard_paciente.php';
        break;

    case 'Recepcionista':
        require __DIR__ . '/views/dashboard_recepcionista.php';
        break;

    case 'Doctor':
        require __DIR__ . '/views/dashboard_doctor.php';
        break;

    case 'Gerente':
    case 'Sistemas':
        require __DIR__ . '/views/dashboard_admin.php';
        break;

    default:
        redirect('logout.php');
}