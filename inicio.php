<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';

$sesionActiva  = isset($_SESSION['usuario_id'], $_SESSION['rol_nombre']);
$nombreUsuario = $_SESSION['usuario_nombre'] ?? '';
$rolActual     = $_SESSION['rol_nombre'] ?? '';

/**
 * Renderiza una imagen si ya existe en assets/img/, o muestra un marco vacío estético listo para tu foto.
 */
function renderSlotImagen(string $archivo, string $etiqueta, string $medidaSugerida, string $claseExtra = ''): string
{
    $rutaFisica = __DIR__ . '/assets/img/' . $archivo;
    $rutaWeb    = htmlspecialchars(url('assets/img/' . $archivo), ENT_QUOTES, 'UTF-8');
    $alt        = htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8');

    if ($archivo !== '' && is_file($rutaFisica)) {
        return "<img src=\"{$rutaWeb}\" alt=\"{$alt}\" class=\"slot-img {$claseExtra}\">";
    }

    return "
    <div class=\"empty-slot {$claseExtra}\">
        <span class=\"slot-icon\">+</span>
        <strong>{$alt}</strong>
        <small>Guardar en: assets/img/{$archivo}</small>
        <small>Sugerido: {$medidaSugerida}</small>
    </div>";
}

$galeriaSlots = [
    ['archivo' => 'galeria-1.jpg', 'titulo' => 'Instalaciones y Recepción Central', 'medida' => '800 × 600 px'],
    ['archivo' => 'galeria-2.jpg', 'titulo' => 'Estación de Enfermería',            'medida' => '800 × 600 px'],
    ['archivo' => 'galeria-3.jpg', 'titulo' => 'Consultorios de Especialidad',      'medida' => '800 × 600 px'],
    ['archivo' => 'galeria-4.jpg', 'titulo' => 'Atención Preventiva y Chequeos',    'medida' => '800 × 600 px'],
    ['archivo' => 'galeria-5.jpg', 'titulo' => 'Área de Diagnóstico Clínico',       'medida' => '800 × 600 px'],
    ['archivo' => 'galeria-6.jpg', 'titulo' => 'Equipo Médico y Administrativo',    'medida' => '800 × 600 px'],
];

$rolesPortal = [
    [
        'rol'         => 'Paciente',
        'archivo'     => 'rol-paciente.jpg',
        'badge'       => 'Afiliado / Asegurado',
        'descripcion' => 'Consulta el catálogo de especialidades, agenda citas médicas sin traslapes de horario y revisa su historial clínico y diagnósticos.',
    ],
    [
        'rol'         => 'Doctor',
        'archivo'     => 'rol-doctor.jpg',
        'badge'       => 'Cuerpo Médico',
        'descripcion' => 'Brinda atención clínica por especialidad, valida identidad de pacientes y registra diagnósticos y notas médicas en el expediente.',
    ],
    [
        'rol'         => 'Recepcionista',
        'archivo'     => 'rol-recepcionista.jpg',
        'badge'       => 'Admisión y Agenda',
        'descripcion' => 'Administra el catálogo de servicios médicos, confirma solicitudes de citas, coordina consultorios y apoya en la admisión.',
    ],
    [
        'rol'         => 'Gerente',
        'archivo'     => 'rol-gerente.jpg',
        'badge'       => 'Dirección General',
        'descripcion' => 'Supervisa la operación clínica, valida identidades de nuevo ingreso, aprueba o rechaza registros y coordina con el área médica.',
    ],
    [
        'rol'         => 'Sistemas',
        'archivo'     => 'rol-sistemas.jpg',
        'badge'       => 'Soporte y Seguridad TI',
        'descripcion' => 'Gestiona la seguridad de accesos, verifica credenciales institucionales, aprueba cuentas y administra las notificaciones oficiales.',
    ],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedicApp — Portal Oficial de Salud y Atención Médica</title>
    <style>
        :root {
            --azul-marino: #0a2540;
            --azul-institucional: #0f4c81;
            --azul-medio: #1d6fa5;
            --azul-cielo: #0284c7;
            --azul-claro: #e0f2fe;
            --azul-hielo: #f4f9fd;
            --blanco: #ffffff;
            --texto-oscuro: #1e293b;
            --texto-suave: #475569;
            --borde-suave: #dbeafe;
            --sombra: 0 10px 25px rgba(15, 76, 129, 0.08);
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--azul-hielo);
            color: var(--texto-oscuro);
            line-height: 1.6;
        }

        /* Barra Superior Institucional */
        .top-strip {
            background: var(--azul-marino);
            color: #e2e8f0;
            font-size: .82rem;
            padding: .45rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: .5rem;
        }
        .top-strip strong { color: #7dd3fc; }

        /* Navegación Principal */
        header.navbar {
            background: var(--blanco);
            border-bottom: 2px solid var(--borde-suave);
            padding: .85rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 12px rgba(10, 37, 64, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .brand-area {
            display: flex;
            align-items: center;
            gap: .9rem;
            text-decoration: none;
            color: var(--azul-marino);
        }

        /* Slot vacío para Logo */
        .logo-box {
            width: 170px;
            height: 54px;
            border: 2px dashed #7dd3fc;
            border-radius: 10px;
            background: #f0f9ff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: .72rem;
            color: var(--azul-institucional);
            overflow: hidden;
        }
/* Ajusta el contenedor compacto al tamaño del emblema y quita el borde punteado */
/* 1. Logo del encabezado (arriba): compacto y con forma de escudo */
header .logo-box:has(img) {
    border: none;
    background: transparent;
    width: 56px;
    height: 56px;
    padding: 0;
    flex-shrink: 0;
}

header .logo-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    clip-path: polygon(50% 0%, 93% 16%, 93% 70%, 50% 100%, 7% 70%, 7% 16%);
}

/* 2. Logo del footer (abajo): rectangular pequeño y sin recortes */
footer .logo-box:has(img) {
    border: none;
    background: transparent;
    width: 135px;
    height: 40px;
    padding: 0;
    border-radius: 6px;
    justify-content: flex-start;
}

footer .logo-box img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    clip-path: none;
    border-radius: 6px;
}
        .brand-title {
            display: flex;
            flex-direction: column;
        }
        .brand-title span.name {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--azul-institucional);
            letter-spacing: -.3px;
        }
        .brand-title span.sub {
            font-size: .76rem;
            color: var(--texto-suave);
            text-transform: uppercase;
            letter-spacing: .8px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.3rem;
            flex-wrap: wrap;
        }
        .nav-links a {
            text-decoration: none;
            color: var(--texto-oscuro);
            font-weight: 600;
            font-size: .92rem;
            transition: color .2s;
        }
        .nav-links a:hover { color: var(--azul-cielo); }

        .auth-buttons {
            display: flex;
            gap: .65rem;
            align-items: center;
        }
        .btn {
            display: inline-block;
            padding: .58rem 1.15rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: .88rem;
            text-decoration: none;
            transition: all .2s ease;
            border: 1px solid transparent;
            cursor: pointer;
        }
        .btn-outline {
            background: var(--blanco);
            color: var(--azul-institucional);
            border-color: var(--azul-institucional);
        }
        .btn-outline:hover {
            background: var(--azul-claro);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--azul-institucional), var(--azul-cielo));
            color: var(--blanco);
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(2, 132, 199, 0.35);
        }

        /* Hero Banner estilo Seguro Médico */
        .hero {
            background: linear-gradient(120deg, var(--azul-marino) 0%, var(--azul-institucional) 60%, var(--azul-cielo) 100%);
            color: var(--blanco);
            padding: 3.5rem 2rem;
        }
        .hero-inner {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 2.5rem;
            align-items: center;
        }
        .hero-pill {
            display: inline-block;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: .3rem .85rem;
            border-radius: 999px;
            font-size: .8rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .hero h1 {
            margin: 0 0 1rem 0;
            font-size: 2.45rem;
            line-height: 1.2;
        }
        .hero p {
            color: #e0f2fe;
            font-size: 1.05rem;
            margin-bottom: 1.8rem;
        }
        .hero-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .btn-white {
            background: var(--blanco);
            color: var(--azul-marino);
        }
        .btn-white:hover {
            background: #f0f9ff;
        }
        .btn-glass {
            background: rgba(255, 255, 255, 0.14);
            color: var(--blanco);
            border-color: rgba(255, 255, 255, 0.45);
        }
        .btn-glass:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        /* Contenedor Principal de 3 Columnas (Noticias Izquierda | Contenido | Chismes Enfermería Derecha) */
        .page-layout {
            max-width: 1380px;
            margin: 2.5rem auto;
            padding: 0 1.2rem;
            display: grid;
            grid-template-columns: 270px minmax(0, 1fr) 270px;
            gap: 1.5rem;
            align-items: start;
        }

        /* Sidebars de Enfermería */
        .sidebar {
            background: var(--blanco);
            border-radius: 14px;
            padding: 1.25rem;
            box-shadow: var(--sombra);
            border-top: 4px solid var(--azul-institucional);
            position: sticky;
            top: 92px;
        }
        .sidebar.gossip {
            border-top-color: var(--azul-cielo);
            background: linear-gradient(180deg, #ffffff 0%, #f8fcff 100%);
        }
        .sidebar h3 {
            margin: 0 0 .3rem 0;
            font-size: 1.05rem;
            color: var(--azul-marino);
            display: flex;
            align-items: center;
            gap: .4rem;
        }
        .sidebar .sidebar-sub {
            font-size: .78rem;
            color: var(--texto-suave);
            margin-bottom: 1rem;
            padding-bottom: .6rem;
            border-bottom: 1px solid var(--borde-suave);
        }
        .news-item {
            padding: .75rem 0;
            border-bottom: 1px solid #eff6ff;
        }
        .news-item:last-child { border-bottom: none; padding-bottom: 0; }
        .news-tag {
            display: inline-block;
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: .15rem .5rem;
            border-radius: 6px;
            background: var(--azul-claro);
            color: var(--azul-institucional);
            margin-bottom: .3rem;
        }
        .gossip .news-tag {
            background: #e0f2fe;
            color: #0369a1;
        }
        .news-item h4 {
            margin: .15rem 0 .3rem 0;
            font-size: .9rem;
            color: var(--azul-marino);
            line-height: 1.35;
        }
        .news-item p {
            margin: 0;
            font-size: .81rem;
            color: var(--texto-suave);
            line-height: 1.45;
        }
        .news-date {
            display: block;
            margin-top: .35rem;
            font-size: .72rem;
            color: #64748b;
        }

        /* Bloques Centrales */
        .section-card {
            background: var(--blanco);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--sombra);
            margin-bottom: 2rem;
            border: 1px solid var(--borde-suave);
        }
        .section-header {
            margin-bottom: 1.5rem;
        }
        .section-kicker {
            color: var(--azul-cielo);
            font-weight: 700;
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .section-header h2 {
            margin: .2rem 0 .4rem 0;
            color: var(--azul-marino);
            font-size: 1.65rem;
        }
        .section-header p {
            margin: 0;
            color: var(--texto-suave);
            font-size: .95rem;
        }

        /* Sobre Nosotros */
        .about-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-top: 1.2rem;
        }
        .about-box {
            background: var(--azul-hielo);
            border: 1px solid var(--borde-suave);
            border-radius: 12px;
            padding: 1.2rem;
        }
        .about-box h4 {
            margin: 0 0 .4rem 0;
            color: var(--azul-institucional);
        }
        .about-box p {
            margin: 0;
            font-size: .88rem;
            color: var(--texto-suave);
        }

        /* Slots vacíos estéticos para fotos */
        .empty-slot {
            width: 100%;
            height: 210px;
            border: 2px dashed #7dd3fc;
            border-radius: 12px;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 1rem;
            color: var(--azul-institucional);
            transition: all .2s ease;
        }
        .empty-slot:hover {
            border-color: var(--azul-institucional);
            background: #e0f2fe;
        }
        .empty-slot .slot-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--blanco);
            color: var(--azul-cielo);
            font-size: 1.4rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: .5rem;
            box-shadow: 0 3px 8px rgba(15, 76, 129, 0.12);
        }
        .empty-slot strong {
            font-size: .88rem;
            margin-bottom: .2rem;
        }
        .empty-slot small {
            font-size: .73rem;
            color: var(--texto-suave);
        }
        .slot-img {
            width: 100%;
            height: 210px;
            object-fit: cover;
            border-radius: 12px;
            display: block;
        }
        .hero-slot { height: 290px; background: rgba(255,255,255,.12); border-color: rgba(255,255,255,.5); color: #fff; }
        .hero-slot small { color: #e0f2fe; }

        /* Galería */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 1.2rem;
        }
        .gallery-card {
            background: var(--blanco);
            border: 1px solid var(--borde-suave);
            border-radius: 12px;
            padding: .75rem;
            transition: transform .2s;
        }
        .gallery-card:hover { transform: translateY(-3px); }
        .gallery-caption {
            margin-top: .6rem;
            font-weight: 600;
            font-size: .88rem;
            color: var(--azul-marino);
            text-align: center;
        }

        /* Roles */
        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.2rem;
        }
        .role-card {
            background: var(--azul-hielo);
            border: 1px solid var(--borde-suave);
            border-radius: 14px;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-align: center;
        }
        .role-slot { height: 170px; border-radius: 10px; }
        .role-badge {
            display: inline-block;
            margin: .75rem auto .3rem auto;
            background: var(--azul-claro);
            color: var(--azul-institucional);
            font-size: .74rem;
            font-weight: 700;
            padding: .2rem .65rem;
            border-radius: 999px;
        }
        .role-card h3 {
            margin: .2rem 0 .4rem 0;
            color: var(--azul-marino);
            font-size: 1.12rem;
        }
        .role-card p {
            margin: 0 0 .9rem 0;
            font-size: .83rem;
            color: var(--texto-suave);
        }

        /* Footer */
        footer {
            background: var(--azul-marino);
            color: #cbd5e1;
            padding: 2.5rem 2rem 1.5rem 2rem;
            margin-top: 3rem;
        }
        .footer-inner {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,.12);
        }
        footer h4 { color: var(--blanco); margin-top: 0; }
        footer a { color: #7dd3fc; text-decoration: none; display: block; margin-bottom: .4rem; font-size: .9rem; }
        .footer-bottom {
            max-width: 1280px;
            margin: 1rem auto 0 auto;
            text-align: center;
            font-size: .82rem;
            color: #94a3b8;
        }

        @media (max-width: 1120px) {
            .page-layout { grid-template-columns: 1fr; }
            .sidebar { position: static; }
            .hero-inner { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- Barra Institucional Superior -->
<div class="top-strip">
    <div><strong>MedicApp Salud Integral</strong> | Atención Clínica y Gestión Hospitalaria Digital</div>
    <div>Línea de Orientación Médica: <strong>800-MEDICAPP</strong> · Servicio 24/7</div>
</div>

<!-- Header con Logo Vacío y Acceso a Login/Registro -->
<header class="navbar">
    <a href="<?= htmlspecialchars(url('inicio.php'), ENT_QUOTES, 'UTF-8') ?>" class="brand-area">
        <div class="logo-box">
            <?php if (is_file(__DIR__ . '/assets/img/logo-medicapp.png')): ?>
                <img src="<?= htmlspecialchars(url('assets/img/logo-medicapp.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Logo MedicApp">
            <?php else: ?>
                <strong>[ ESPACIO PARA LOGO ]</strong>
                <span>assets/img/logo-medicapp.png</span>
            <?php endif; ?>
        </div>
        <div class="brand-title">
            <span class="name">MedicApp</span>
            <span class="sub">Red Médica y Protección Familiar</span>
        </div>
    </a>

    <nav class="nav-links">
        <a href="#sobre-nosotros">Sobre Nosotros</a>
        <a href="#galeria">Galería</a>
        <a href="#roles">Roles del Portal</a>
        <a href="#noticias-enfermeria">Enfermería</a>
    </nav>

    <div class="auth-buttons">
        <?php if ($sesionActiva): ?>
            <span style="font-size:.85rem; color:var(--azul-institucional); font-weight:600;">
                Hola, <?= htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($rolActual, ENT_QUOTES, 'UTF-8') ?>)
            </span>
            <a href="<?= htmlspecialchars(url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">Ir a Mi Panel</a>
            <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline">Salir</a>
        <?php else: ?>
            <a href="<?= htmlspecialchars(url('login.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline">Iniciar Sesión</a>
            <a href="<?= htmlspecialchars(url('register.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">Solicitar Registro</a>
        <?php endif; ?>
    </div>
</header>

<!-- Hero Principal -->
<section class="hero">
    <div class="hero-inner">
        <div>
            <span class="hero-pill">Portal Oficial de Afiliados y Personal Clínico</span>
            <h1>Tu salud respaldada por atención médica oportuna y segura</h1>
            <p>
                Bienvenido a la plataforma oficial de <strong>MedicApp</strong>. Consulta nuestras especialidades,
                agenda tus citas médicas sin tiempos de espera y accede a tu expediente clínico digital con total confidencialidad.
            </p>
            <div class="hero-actions">
                <a href="<?= htmlspecialchars(url('login.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-white">Ingresar al Portal (Login)</a>
                <a href="<?= htmlspecialchars(url('register.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-glass">Crear Cuenta Nueva</a>
            </div>
        </div>
        <div>
            <?= renderSlotImagen('hero-clinica.jpg', 'Imagen Principal de Portada Clínica', '900 × 600 px', 'hero-slot') ?>
        </div>
    </div>
</section>

<!-- Cuerpo Principal de 3 Columnas -->
<div class="page-layout" id="noticias-enfermeria">

    <!-- COLUMNA IZQUIERDA: Noticias Oficiales de Enfermería -->
    <aside class="sidebar">
        <h3>Noticias de Enfermería</h3>
        <div class="sidebar-sub">Boletín clínico y actualizaciones del departamento</div>

        <article class="news-item">
            <span class="news-tag">Campaña Preventiva</span>
            <h4>Jornada de vacunación y control de signos vitales</h4>
            <p>El equipo de enfermería estará realizando toma gratuita de glucosa y presión arterial en el módulo central de lunes a viernes.</p>
            <span class="news-date">Actualizado · Área de Prevención</span>
        </article>

        <article class="news-item">
            <span class="news-tag">Protocolo Clínico</span>
            <h4>Nuevo sistema digital de triage y puntualidad en citas</h4>
            <p>Gracias a la agenda inteligente de MedicApp, los tiempos de espera en sala se redujeron un 40% durante este trimestre.</p>
            <span class="news-date">Actualizado · Coordinación Médica</span>
        </article>

        <article class="news-item">
            <span class="news-tag">Capacitación</span>
            <h4>Certificación en atención humanizada y primeros auxilios</h4>
            <p>Nuestras enfermeras y enfermeros concluyeron el taller anual de soporte vital básico y atención al paciente.</p>
            <span class="news-date">Actualizado · Enseñanza Clínica</span>
        </article>

        <article class="news-item">
            <span class="news-tag">Aviso Afiliados</span>
            <h4>Recomendaciones previas a estudios y consulta general</h4>
            <p>Recuerda presentarte 10 minutos antes de tu cita confirmada y mantener actualizados tus datos de contacto.</p>
            <span class="news-date">Actualizado · Atención al Paciente</span>
        </article>
    </aside>

    <!-- COLUMNA CENTRAL: Sobre Nosotros + Galería Vacía + Apartado de Roles -->
    <main>
        <!-- Sección Sobre Nosotros -->
        <section class="section-card" id="sobre-nosotros">
            <div class="section-header">
                <span class="section-kicker">Identidad Institucional</span>
                <h2>Sobre Nosotros</h2>
                <p>
                    En <strong>MedicApp</strong> combinamos calidez humana, rigor clínico y tecnología digital para brindar
                    protección médica integral a nuestros pacientes, médicos y personal administrativo.
                </p>
            </div>

            <div class="about-grid">
                <div class="about-box">
                    <h4>Misión</h4>
                    <p>Garantizar servicios médicos accesibles, puntuales y seguros mediante una gestión digital transparente entre pacientes y especialistas.</p>
                </div>
                <div class="about-box">
                    <h4>Visión</h4>
                    <p>Ser la red clínica de referencia por su excelencia en enfermería, atención preventiva y eficiencia hospitalaria sin filas ni traslapes.</p>
                </div>
                <div class="about-box">
                    <h4>Seguridad y Validación</h4>
                    <p>Cada cuenta es verificada por Gerencia y Sistemas para proteger la integridad de los expedientes y la identidad de nuestros afiliados.</p>
                </div>
            </div>
        </section>

        <!-- Sección Galería de Fotos (Slots vacíos listos para llenar) -->
        <section class="section-card" id="galeria">
            <div class="section-header">
                <span class="section-kicker">Nuestras Instalaciones</span>
                <h2>Galería Institucional</h2>
                <p>Conoce nuestros consultorios, áreas de enfermería y espacios diseñados para tu bienestar.</p>
            </div>

            <div class="gallery-grid">
                <?php foreach ($galeriaSlots as $item): ?>
                    <div class="gallery-card">
                        <?= renderSlotImagen($item['archivo'], $item['titulo'], $item['medida']) ?>
                        <div class="gallery-caption"><?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Sección Roles del Sistema (Con espacios para fotos de cada rol) -->
        <section class="section-card" id="roles">
            <div class="section-header">
                <span class="section-kicker">Estructura y Comunidad</span>
                <h2>Roles en MedicApp</h2>
                <p>Cada perfil cuenta con un panel especializado y permisos protegidos dentro de nuestra plataforma.</p>
            </div>

            <div class="roles-grid">
                <?php foreach ($rolesPortal as $r): ?>
                    <div class="role-card">
                        <div>
                            <?= renderSlotImagen($r['archivo'], 'Foto Rol: ' . $r['rol'], '500 × 400 px', 'role-slot') ?>
                            <span class="role-badge"><?= htmlspecialchars($r['badge'], ENT_QUOTES, 'UTF-8') ?></span>
                            <h3><?= htmlspecialchars($r['rol'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p><?= htmlspecialchars($r['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <a href="<?= htmlspecialchars(url('login.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline" style="width:100%;">
                            Acceder como <?= htmlspecialchars($r['rol'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <!-- COLUMNA DERECHA: Pasillos y Chismes de Enfermería -->
    <aside class="sidebar gossip">
        <h3>Pasillos de Enfermería</h3>
        <div class="sidebar-sub">Lo que se comenta en la estación y cambio de turno</div>

        <article class="news-item">
            <span class="news-tag">Turno Nocturno</span>
            <h4>¿Quién se acabó el café de la estación 2?</h4>
            <p>Dicen en guardia nocturna que entre receta y receta desapareció la cafetera recién hecha antes de las 3:00 a.m. ¡Se busca sospechoso con bata blanca!</p>
            <span class="news-date">Estación de Enfermería · Hoy</span>
        </article>

        <article class="news-item">
            <span class="news-tag">Radio Pasillo</span>
            <h4>El estetoscopio perdido que apareció en Cardiología</h4>
            <p>Después de buscarlo por medio hospital, resultó que el Dr. Vega lo traía colgado desde la consulta de la mañana. Final feliz en piso 2.</p>
            <span class="news-date">Consultorios · Hace 2 horas</span>
        </article>

        <article class="news-item">
            <span class="news-tag">Comunidad</span>
            <h4>Festejo sorpresa de cumpleaños en Recepción</h4>
            <p>Entre llamadas y confirmaciones de citas, el equipo de enfermería y recepción celebró con pastel sin descuidar ni un solo paciente.</p>
            <span class="news-date">Área Administrativa · Ayer</span>
        </article>

        <article class="news-item">
            <span class="news-tag">Dato Curioso</span>
            <h4>¡Ya no hay peleas por los horarios de consulta!</h4>
            <p>Comentan las enfermeras que desde que Sistemas activó el bloqueo anti-traslapes de MedicApp, todos salen a tiempo a su hora de comida.</p>
            <span class="news-date">Pasillo Central · Esta semana</span>
        </article>
    </aside>

</div>

<!-- Footer Oficial -->
<footer>
    <div class="footer-inner">
        <div>
            <div class="logo-box" style="background:rgba(255,255,255,.08); border-color:#38bdf8; color:#e0f2fe; margin-bottom:.8rem;">
                <?php if (is_file(__DIR__ . '/assets/img/logo-medicapp-blanco.png')): ?>
                    <img src="<?= htmlspecialchars(url('assets/img/logo-medicapp-blanco.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Logo MedicApp Footer">
                <?php else: ?>
                    <strong>[ LOGO FOOTER ]</strong>
                    <span>assets/img/logo-medicapp-blanco.png</span>
                <?php endif; ?>
            </div>
            <p style="font-size:.86rem; margin:0;">
                Plataforma oficial de gestión de servicios médicos, validación de afiliados y control de citas clínicas.
            </p>
        </div>

        <div>
            <h4>Accesos Rápidos</h4>
            <a href="<?= htmlspecialchars(url('login.php'), ENT_QUOTES, 'UTF-8') ?>">Iniciar Sesión</a>
            <a href="<?= htmlspecialchars(url('register.php'), ENT_QUOTES, 'UTF-8') ?>">Solicitar Registro</a>
            <a href="#sobre-nosotros">Sobre Nosotros</a>
            <a href="#galeria">Galería de Instalaciones</a>
        </div>

        <div>
            <h4>Roles Autorizados</h4>
            <a href="#roles">Portal del Paciente</a>
            <a href="#roles">Cuerpo Médico (Doctores)</a>
            <a href="#roles">Recepción y Admisión</a>
            <a href="#roles">Gerencia y Sistemas</a>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?= date('Y') ?> MedicApp — Equipo 3 · Todos los derechos reservados.
    </div>
</footer>

</body>
</html>