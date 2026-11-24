# 🏥 MedicApp — Portal Oficial de Salud y Gestión Hospitalaria

![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL%20%2F%20MariaDB-PDO-4479A1?style=flat-square&logo=mysql&logoColor=white)
![Estado](https://img.shields.io/badge/Estado-Funcional-2ea44f?style=flat-square)

**MedicApp** es una plataforma web integral diseñada para la gestión clínica, el control de citas médicas sin traslapes de horario y la administración de servicios hospitalarios. Combina un portal institucional informativo con un sistema seguro de acceso basado en 5 roles y validación estricta de identidad.

---

## ✨ Módulos y Características Principales

### 🔐 1. Autenticación y Validación de Identidad (`login.php`, `register.php`, `admin/`)
* **Registro con Flujo de Aprobación:** Toda cuenta nueva inicia en estado `Pendiente` (`activo = 0`) hasta que **Gerencia** o **Sistemas** validan la identidad del solicitante desde `admin/aprobaciones.php`.
* **Gestión de Estados y Auditoría:** Soporte para aprobación, rechazo con motivo registrado (`motivo_rechazo`) y consulta histórica de solicitudes en `admin/consultas_registro.php`.
* **Seguridad de Sesión:** Contraseñas cifradas con `password_hash()`, regeneración de ID de sesión (`session_regenerate_id(true)`) y limpieza completa de cookies en `logout.php`.

### 📅 2. Gestión de Citas y Expediente Clínico (`citas/`)
* **Agendamiento Inteligente (`citas/agendar.php`):** Permite a los pacientes reservar consultas por especialidad con bloqueo automático anti-traslapes de horario.
* **Control y Atención Clínica (`citas/gestionar.php`):** Confirmación de citas por Recepción y registro de diagnósticos y notas médicas por el Cuerpo Médico.
* **Historial Clínico (`citas/historial.php`):** Consulta organizada de atenciones previas, especialistas y diagnósticos realizados.

### 🩺 3. Catálogo de Servicios Médicos (`servicios/`)
* **Administración CRUD Completa:** Visualización del catálogo institucional (`servicios/index.php`), alta de nuevas especialidades o estudios (`servicios/crear.php`) y actualización de información o tarifas (`servicios/editar.php`).

### ✉️ 4. Notificaciones y Gestión Multimedia (`includes/`, `admin/buzon_correos.php`)
* **Sistema de Correo Institucional (`includes/mail_helper.php`):** Envío automatizado de notificaciones de registro y aprobación, respaldado por un buzón de seguimiento en `admin/buzon_correos.php`.
* **Carga Segura de Archivos (`includes/upload_helper.php`):** Utilidad dedicada para validar y almacenar recursos gráficos de forma segura.

### 🏛️ 5. Portal Institucional (`inicio.php`)
* Diseño responsivo de 3 columnas con identidad visual médica que integra:
  * **Boletín Oficial de Enfermería** (columna izquierda).
  * **Identidad Institucional, Galería y Perfiles** (columna central).
  * **Pasillos de Enfermería** (columna derecha).

---

## 👥 Roles y Paneles del Sistema (`views/`)

El enrutador principal (`index.php`) redirige automáticamente a cada usuario autenticado hacia su panel correspondiente:

| Rol | Vista Asignada | Responsabilidades y Permisos |
| :--- | :--- | :--- |
| **Paciente** | `views/dashboard_paciente.php` | Explora el catálogo de especialidades, agenda citas sin traslapes y consulta su historial clínico y diagnósticos. |
| **Doctor** | `views/dashboard_doctor.php` | Atiende consultas de su especialidad, valida pacientes y registra diagnósticos y notas médicas. |
| **Recepcionista** | `views/dashboard_recepcionista.php` | Administra el catálogo de servicios médicos, confirma solicitudes de citas y coordina la admisión. |
| **Gerente** | `views/dashboard_admin.php` | Supervisa la operación clínica, valida identidades de nuevo ingreso y aprueba o rechaza registros. |
| **Sistemas** | `views/dashboard_admin.php` | Administra la seguridad de accesos, audita registros, aprueba cuentas y supervisa el buzón de correos. |

---

## 📁 Estructura del Proyecto (`htdocs/`)

```text
htdocs/
├── admin/
│   ├── aprobaciones.php            # Aprobación y rechazo de cuentas pendientes
│   ├── buzon_correos.php           # Historial y control de notificaciones enviadas
│   └── consultas_registro.php      # Auditoría y consulta de solicitudes de registro
├── assets/
│   └── img/                        # Recursos gráficos institucionales (14 imágenes)
│       ├── logo-medicapp.png       # Logotipo principal (encabezado)
│       ├── logo-medicapp-blanco.png# Logotipo institucional (pie de página)
│       ├── hero-clinica.jpg        # Imagen principal de portada
│       ├── galeria-1.jpg .. 6.jpg  # Fotografías de instalaciones y consultorios
│       └── rol-*.jpg               # Retratos por rol (paciente, doctor, recepcionista, gerente, sistemas)
├── citas/
│   ├── agendar.php                 # Reserva de citas médicas con validación de horarios
│   ├── gestionar.php               # Confirmación de citas y registro de diagnósticos
│   └── historial.php               # Consulta de historial clínico y citas pasadas
├── config/
│   └── database.php                # Conexión PDO (getConnection) y helpers (url, redirect)
├── includes/
│   ├── mail_helper.php             # Envío y registro de correos electrónicos
│   └── upload_helper.php           # Validación y carga segura de imágenes/archivos
├── servicios/
│   ├── index.php                   # Listado y catálogo de servicios médicos
│   ├── crear.php                   # Registro de nuevos servicios o especialidades
│   └── editar.php                  # Edición de servicios existentes
├── views/
│   ├── dashboard_admin.php         # Panel de control para Gerencia y Sistemas
│   ├── dashboard_doctor.php        # Panel de control para Médicos
│   ├── dashboard_paciente.php      # Panel de control para Pacientes
│   └── dashboard_recepcionista.php # Panel de control para Recepción
├── index.php                       # Controlador frontal y enrutador por roles
├── inicio.php                      # Portal público institucional de bienvenida
├── login.php                       # Inicio de sesión con verificación de estado
├── logout.php                      # Cierre seguro de sesión y limpieza de cookies
├── register.php                    # Formulario de solicitud de registro
└── README.md                       # Documentación técnica del proyecto
```

---

## 🚀 Instalación y Despliegue

### Requisitos
* **PHP 8.0** o superior con la extensión `pdo_mysql` activa.
* **MySQL** o **MariaDB**.
* Servidor web **Apache / Nginx** (compatible tanto con entornos locales como XAMPP/Laragon como con hosting compartido bajo carpeta `htdocs/`).

### Pasos de Configuración
1. **Subir archivos:** Coloca todo el contenido dentro de tu directorio raíz web (`htdocs/`).
2. **Base de datos:** Crea la base de datos en MySQL e importa la estructura de tablas (`roles`, `usuarios`, `servicios`, `citas`, `correos`, etc.).
3. **Credenciales:** Edita el archivo `config/database.php` con los datos de tu servidor MySQL (`host`, `dbname`, `usuario`, `contraseña`) y ajusta la ruta base en la función `url()`.
4. **Acceso:** Abre tu navegador en la URL de tu servidor local (`http://localhost/medicapp/`) o en tu dominio alojado.

---
👨‍💻 Créditos

Desarrollado por **Equipo 3 — MedicApp**.
Aracely, 
Estefania, 
Karol Aimee, 
