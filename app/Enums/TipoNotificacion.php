<?php

namespace App\Enums;

/**
 * Enum de Tipos de Notificaciones
 *
 * Define los tipos de notificaciones que el sistema puede enviar.
 * Cada notificación se registra en BD (tabla notificaciones) y se envía por email vía Mailgun.
 *
 * ARQUITECTURA DUAL:
 * - BD: Registro histórico, visualización en dashboard web, marca de leído/no leído
 * - Email: Delivery inmediato a usuarios móviles (docentes/estudiantes) y web (gestores)
 *
 * @package App\Enums
 */
enum TipoNotificacion: string
{
    // ========================================
    // RECORDATORIOS DE CLASE
    // ========================================

    /**
     * Recordatorio de clase próxima para DOCENTE
     * Timing: 1 hora + 30 minutos antes de la clase
     * Destinatario: Docente asignado al grupo
     * Canal: BD + Email
     */
    case CLASE_PROXIMA_DOCENTE = 'clase_proxima_docente';

    /**
     * Recordatorio de clase próxima para ESTUDIANTE
     * Timing: 15 minutos antes de la clase
     * Destinatario: Estudiantes inscritos en el grupo
     * Canal: BD + Email
     */
    case CLASE_PROXIMA_ESTUDIANTE = 'clase_proxima_estudiante';

    // ========================================
    // GESTIÓN DE INSCRIPCIONES
    // ========================================

    /**
     * Solicitud de inscripción recibida
     * Timing: Inmediato al crearse la solicitud
     * Destinatario: Docente del grupo solicitado
     * Canal: BD + Email
     */
    case SOLICITUD_INSCRIPCION = 'solicitud_inscripcion';

    /**
     * Inscripción aprobada
     * Timing: Inmediato al aprobar la solicitud
     * Destinatario: Estudiante que solicitó
     * Canal: BD + Email
     */
    case INSCRIPCION_APROBADA = 'inscripcion_aprobada';

    /**
     * Inscripción rechazada
     * Timing: Inmediato al rechazar la solicitud
     * Destinatario: Estudiante que solicitó
     * Canal: BD + Email
     */
    case INSCRIPCION_RECHAZADA = 'inscripcion_rechazada';

    /**
     * Solicitud de inscripción expirada por tiempo
     * Timing: Automático después de 30 días sin respuesta
     * Destinatario: Estudiante que solicitó
     * Canal: BD + Email
     */
    case SOLICITUD_EXPIRADA = 'solicitud_expirada';

    // ========================================
    // GESTIÓN DE SESIONES DE CLASE
    // ========================================

    /**
     * Sesión de clase no cerrada después del período de gracia
     * Timing: Automático después de tiempo de gracia + buffer
     * Destinatario: Docente responsable de la sesión
     * Canal: BD + Email
     */
    case SESION_NO_CERRADA = 'sesion_no_cerrada';

    // ========================================
    // MANTENIMIENTOS Y ADMINISTRACIÓN
    // ========================================

    /**
     * Alerta de mantenimientos pendientes
     * Timing: Semanal (viernes 9 AM)
     * Destinatario: Gestores de departamento/carrera
     * Canal: BD + Email
     */
    case MANTENIMIENTO_PENDIENTE = 'mantenimiento_pendiente';

    /**
     * Validación de integridad de datos
     * Timing: Semanal (domingos 3 AM)
     * Destinatario: Administradores del sistema
     * Canal: BD + Email (solo si hay inconsistencias)
     */
    case INTEGRIDAD_DATOS = 'integridad_datos';

    /**
     * Usuario inactivo próximo a eliminación
     * Timing: 25 días de inactividad (5 días antes de eliminación)
     * Destinatario: Usuario inactivo
     * Canal: BD + Email
     */
    case USUARIO_INACTIVO_ALERTA = 'usuario_inactivo_alerta';

    /**
     * Reporte de estadísticas generado
     * Timing: Nocturno (diario/semanal/mensual según configuración)
     * Destinatario: Administradores y gestores
     * Canal: BD + Email con adjunto
     */
    case REPORTE_ESTADISTICAS = 'reporte_estadisticas';

    // ========================================
    // MÉTODOS AUXILIARES
    // ========================================

    /**
     * Obtiene el título legible del tipo de notificación
     */
    public function titulo(): string
    {
        return match ($this) {
            self::CLASE_PROXIMA_DOCENTE => 'Clase Próxima',
            self::CLASE_PROXIMA_ESTUDIANTE => 'Clase Próxima',
            self::SOLICITUD_INSCRIPCION => 'Nueva Solicitud de Inscripción',
            self::INSCRIPCION_APROBADA => 'Inscripción Aprobada',
            self::INSCRIPCION_RECHAZADA => 'Inscripción Rechazada',
            self::SOLICITUD_EXPIRADA => 'Solicitud de Inscripción Expirada',
            self::SESION_NO_CERRADA => 'Sesión de Clase No Cerrada',
            self::MANTENIMIENTO_PENDIENTE => 'Mantenimientos Pendientes',
            self::INTEGRIDAD_DATOS => 'Validación de Integridad',
            self::USUARIO_INACTIVO_ALERTA => 'Alerta de Inactividad',
            self::REPORTE_ESTADISTICAS => 'Reporte de Estadísticas',
        };
    }

    /**
     * Determina si esta notificación debe enviarse por email
     */
    public function debeEnviarEmail(): bool
    {
        // Todas las notificaciones se envían por email según arquitectura dual
        return true;
    }

    /**
     * Determina la prioridad de la notificación
     * @return string 'alta'|'media'|'baja'
     */
    public function prioridad(): string
    {
        return match ($this) {
            self::CLASE_PROXIMA_DOCENTE,
            self::CLASE_PROXIMA_ESTUDIANTE => 'alta',

            self::SOLICITUD_INSCRIPCION,
            self::SESION_NO_CERRADA,
            self::MANTENIMIENTO_PENDIENTE => 'media',

            self::INSCRIPCION_APROBADA,
            self::INSCRIPCION_RECHAZADA,
            self::SOLICITUD_EXPIRADA,
            self::INTEGRIDAD_DATOS,
            self::USUARIO_INACTIVO_ALERTA,
            self::REPORTE_ESTADISTICAS => 'baja',
        };
    }

    /**
     * Determina si esta notificación es crítica (requiere acción inmediata)
     */
    public function esCritica(): bool
    {
        return match ($this) {
            self::CLASE_PROXIMA_DOCENTE,
            self::CLASE_PROXIMA_ESTUDIANTE,
            self::SESION_NO_CERRADA => true,
            default => false,
        };
    }
}
