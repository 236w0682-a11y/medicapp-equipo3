# Documentación del Proyecto — MedicApp

Este documento reúne la estructura de la base de datos, los pasos para levantar el proyecto en servidor local o hosting y las cuentas listas para probar el sistema clínico.

**Desarrollado por:** Equipo 3 Aracely Feliciano Juárez,
                               Estefania Suarez Ramos,
                               Karol Aimee Zarate Perez,
**Carrera:** Ingeniería en Sistemas Computacionales — 7° Semestre 

---

## 1. Diagrama Entidad-Relación (Base de Datos)

La base de datos `medicapp` (`if0_43450307_medicapp`) está diseñada en MySQL/MariaDB para gestionar el control de acceso por roles, la validación de identidad de nuevos usuarios, el catálogo de especialidades médicas, el agendamiento de citas sin traslapes de horario y el historial de notificaciones por correo.

```mermaid
erDiagram
    ROLES {
        int id PK
        varchar nombre "Paciente | Doctor | Recepcionista | Gerente | Sistemas"
    }

    USUARIOS {
        int id PK
        int rol_id FK
        varchar nombre
        varchar apellido
        varchar email
        varchar password_hash
        varchar telefono
        enum estado_aprobacion "Pendiente | Aprobado | Rechazado"
        varchar motivo_rechazo
        int aprobado_por FK
        tinyint activo
        datetime created_at
    }

    SERVICIOS_MEDICOS {
        int id PK
        varchar especialidad
        varchar medico
        decimal costo
        int duracion_minutos
        varchar foto
        tinyint activo
    }

    CITAS {
        int id PK
        int paciente_id FK
        int servicio_id FK
        date fecha_cita
        time hora_inicio
        time hora_fin
        decimal costo_acordado
        enum estado "Pendiente | Confirmada | Completada | Cancelada"
        text motivo_consulta
        text notas_medicas
    }

    CONSULTAS_VALIDACION {
        int id PK
        int usuario_pendiente_id FK
        int remitente_id FK
        int destinatario_id FK
        text mensaje
        text respuesta
        datetime respondido_at
        datetime created_at
    }

    NOTIFICACIONES_CORREO {
        int id PK
        varchar destinatario_email
        varchar destinatario_nombre
        varchar asunto
        text mensaje
        tinyint enviado_smtp
        datetime created_at
    }

    ROLES ||--o{ USUARIOS : "define permisos de"
    USUARIOS ||--o{ USUARIOS : "aprueba registro de"
    USUARIOS ||--o{ CITAS : "agenda"
    SERVICIOS_MEDICOS ||--o{ CITAS : "se programa en"
    USUARIOS ||--o{ CONSULTAS_VALIDACION : "es verificado en"
    USUARIOS ||--o{ CONSULTAS_VALIDACION : "envia consulta"
    USUARIOS ||--o{ CONSULTAS_VALIDACION : "responde validacion"
```
