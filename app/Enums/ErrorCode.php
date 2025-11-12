<?php

namespace App\Enums;

/**
 * Catálogo centralizado de códigos de error del sistema
 *
 * Cada código representa un error específico de lógica de negocio
 * y mapea a un código HTTP apropiado.
 *
 * Convención de nombres: CATEGORY_SPECIFIC_ERROR
 * Ejemplos:
 * - AUTH_UNAUTHORIZED (Autenticación/Autorización)
 * - GROUP_FULL (Inscripciones)
 * - SCHEDULE_CONFLICT (Horarios)
 *
 * @package App\Enums
 * @version 1.0.0
 */
enum ErrorCode: string
{
    // ========================================
    // AUTENTICACIÓN Y AUTORIZACIÓN (40X)
    // ========================================

    /** Usuario no tiene permiso para realizar esta acción */
    case UNAUTHORIZED = 'UNAUTHORIZED';

    /** Rol insuficiente para la operación */
    case INSUFFICIENT_ROLE = 'INSUFFICIENT_ROLE';

    /** Credenciales inválidas (usuario/contraseña incorrectos) */
    case INVALID_CREDENTIALS = 'INVALID_CREDENTIALS';

    /** Token de acceso expirado */
    case TOKEN_EXPIRED = 'TOKEN_EXPIRED';

    /** Token de acceso inválido o malformado */
    case INVALID_TOKEN = 'INVALID_TOKEN';

    /** Usuario no es el docente asignado al horario/grupo */
    case NOT_ASSIGNED_TEACHER = 'NOT_ASSIGNED_TEACHER';

    /** Usuario no es gestor del departamento/carrera */
    case NOT_MANAGER = 'NOT_MANAGER';

    // ========================================
    // INSCRIPCIONES (42X)
    // ========================================

    /** Grupo ha alcanzado su capacidad máxima */
    case GROUP_FULL = 'GROUP_FULL';

    /** Estudiante ya está inscrito en este grupo */
    case ALREADY_ENROLLED = 'ALREADY_ENROLLED';

    /** Estudiante ya está inscrito en un grupo de esta materia */
    case ALREADY_ENROLLED_IN_SUBJECT = 'ALREADY_ENROLLED_IN_SUBJECT';

    /** Periodo de inscripciones cerrado */
    case ENROLLMENT_CLOSED = 'ENROLLMENT_CLOSED';

    /** Ciclo académico inactivo o finalizado */
    case INACTIVE_CYCLE = 'INACTIVE_CYCLE';

    /** Solicitud de inscripción no encontrada */
    case ENROLLMENT_REQUEST_NOT_FOUND = 'ENROLLMENT_REQUEST_NOT_FOUND';

    /** Solicitud de inscripción ya procesada */
    case ENROLLMENT_REQUEST_ALREADY_PROCESSED = 'ENROLLMENT_REQUEST_ALREADY_PROCESSED';

    /** Solicitud de inscripción expirada */
    case ENROLLMENT_REQUEST_EXPIRED = 'ENROLLMENT_REQUEST_EXPIRED';

    // ========================================
    // HORARIOS (40X)
    // ========================================

    /** Conflicto de horario en el aula */
    case SCHEDULE_CONFLICT = 'SCHEDULE_CONFLICT';

    /** Docente ya tiene otra clase asignada en este horario */
    case TEACHER_SCHEDULE_CONFLICT = 'TEACHER_SCHEDULE_CONFLICT';

    /** Estudiante tiene conflicto de horario entre materias */
    case STUDENT_SCHEDULE_CONFLICT = 'STUDENT_SCHEDULE_CONFLICT';

    /** Horario fuera del rango permitido (ej: 22:00 PM - 05:00 AM) */
    case INVALID_TIME_RANGE = 'INVALID_TIME_RANGE';

    /** Fecha de fin anterior a fecha de inicio */
    case INVALID_DATE_RANGE = 'INVALID_DATE_RANGE';

    // ========================================
    // AULAS Y RECURSOS (40X)
    // ========================================

    /** Aula no disponible (ocupada, en mantenimiento, etc.) */
    case CLASSROOM_NOT_AVAILABLE = 'CLASSROOM_NOT_AVAILABLE';

    /** Aula en mantenimiento */
    case CLASSROOM_IN_MAINTENANCE = 'CLASSROOM_IN_MAINTENANCE';

    /** Recurso no disponible */
    case RESOURCE_NOT_AVAILABLE = 'RESOURCE_NOT_AVAILABLE';

    /** Recurso en mantenimiento */
    case RESOURCE_IN_MAINTENANCE = 'RESOURCE_IN_MAINTENANCE';

    /** Capacidad del aula excedida */
    case CLASSROOM_CAPACITY_EXCEEDED = 'CLASSROOM_CAPACITY_EXCEEDED';

    // ========================================
    // SESIONES DE CLASE (40X)
    // ========================================

    /** Sesión de clase ya fue iniciada */
    case SESSION_ALREADY_STARTED = 'SESSION_ALREADY_STARTED';

    /** No hay sesión activa en este momento */
    case SESSION_NOT_ACTIVE = 'SESSION_NOT_ACTIVE';

    /** Sesión ya fue finalizada */
    case SESSION_ALREADY_ENDED = 'SESSION_ALREADY_ENDED';

    /** No se puede finalizar sesión que no está activa */
    case CANNOT_END_INACTIVE_SESSION = 'CANNOT_END_INACTIVE_SESSION';

    /** Sesión no pertenece a este horario */
    case SESSION_HORARIO_MISMATCH = 'SESSION_HORARIO_MISMATCH';

    // ========================================
    // ASISTENCIAS (40X)
    // ========================================

    /** No hay clase en curso en esta aula */
    case NO_ACTIVE_SESSION = 'NO_ACTIVE_SESSION';

    /** Estudiante no está inscrito en este grupo */
    case STUDENT_NOT_ENROLLED = 'STUDENT_NOT_ENROLLED';

    /** Código QR inválido o no reconocido */
    case INVALID_QR = 'INVALID_QR';

    /** Código QR expirado */
    case EXPIRED_QR = 'EXPIRED_QR';

    /** Asistencia ya fue registrada */
    case ATTENDANCE_ALREADY_REGISTERED = 'ATTENDANCE_ALREADY_REGISTERED';

    /** Fuera del tiempo permitido para registrar asistencia */
    case ATTENDANCE_TIME_WINDOW_CLOSED = 'ATTENDANCE_TIME_WINDOW_CLOSED';

    /** Aula del QR no coincide con aula de la sesión */
    case QR_CLASSROOM_MISMATCH = 'QR_CLASSROOM_MISMATCH';

    // ========================================
    // VALIDACIÓN GENERAL (422)
    // ========================================

    /** Error de validación de datos */
    case VALIDATION_ERROR = 'VALIDATION_ERROR';

    /** Datos requeridos faltantes */
    case MISSING_REQUIRED_DATA = 'MISSING_REQUIRED_DATA';

    /** Formato de datos inválido */
    case INVALID_DATA_FORMAT = 'INVALID_DATA_FORMAT';

    // ========================================
    // RECURSOS NO ENCONTRADOS (404)
    // ========================================

    /** Recurso genérico no encontrado */
    case NOT_FOUND = 'NOT_FOUND';

    /** Usuario no encontrado */
    case USER_NOT_FOUND = 'USER_NOT_FOUND';

    /** Aula no encontrada */
    case CLASSROOM_NOT_FOUND = 'CLASSROOM_NOT_FOUND';

    /** Horario no encontrado */
    case SCHEDULE_NOT_FOUND = 'SCHEDULE_NOT_FOUND';

    /** Grupo no encontrado */
    case GROUP_NOT_FOUND = 'GROUP_NOT_FOUND';

    /** Materia no encontrada */
    case SUBJECT_NOT_FOUND = 'SUBJECT_NOT_FOUND';

    // ========================================
    // ERRORES DEL SISTEMA (50X)
    // ========================================

    /** Error de base de datos */
    case DATABASE_ERROR = 'DATABASE_ERROR';

    /** Error de servicio externo (email, APIs, etc.) */
    case EXTERNAL_SERVICE_ERROR = 'EXTERNAL_SERVICE_ERROR';

    /** Error inesperado del sistema */
    case UNEXPECTED_ERROR = 'UNEXPECTED_ERROR';

    /** Servicio temporalmente no disponible */
    case SERVICE_UNAVAILABLE = 'SERVICE_UNAVAILABLE';

    /**
     * Obtener código HTTP sugerido para este error
     *
     * @return int Código HTTP
     */
    public function httpStatus(): int
    {
        return match ($this) {
            // 401 Unauthorized
            self::INVALID_CREDENTIALS,
            self::TOKEN_EXPIRED,
            self::INVALID_TOKEN
                => 401,

            // 403 Forbidden
            self::UNAUTHORIZED,
            self::INSUFFICIENT_ROLE,
            self::NOT_ASSIGNED_TEACHER,
            self::NOT_MANAGER,
            self::STUDENT_NOT_ENROLLED,
            self::ENROLLMENT_CLOSED
                => 403,

            // 404 Not Found
            self::NO_ACTIVE_SESSION,
            self::SESSION_NOT_ACTIVE,
            self::NOT_FOUND,
            self::USER_NOT_FOUND,
            self::CLASSROOM_NOT_FOUND,
            self::SCHEDULE_NOT_FOUND,
            self::GROUP_NOT_FOUND,
            self::SUBJECT_NOT_FOUND,
            self::ENROLLMENT_REQUEST_NOT_FOUND
                => 404,

            // 409 Conflict
            self::SCHEDULE_CONFLICT,
            self::TEACHER_SCHEDULE_CONFLICT,
            self::STUDENT_SCHEDULE_CONFLICT,
            self::SESSION_ALREADY_STARTED,
            self::SESSION_ALREADY_ENDED,
            self::ALREADY_ENROLLED,
            self::ALREADY_ENROLLED_IN_SUBJECT,
            self::ATTENDANCE_ALREADY_REGISTERED,
            self::ENROLLMENT_REQUEST_ALREADY_PROCESSED
                => 409,

            // 422 Unprocessable Entity
            self::VALIDATION_ERROR,
            self::GROUP_FULL,
            self::INACTIVE_CYCLE,
            self::INVALID_DATE_RANGE,
            self::INVALID_TIME_RANGE,
            self::CLASSROOM_CAPACITY_EXCEEDED,
            self::CANNOT_END_INACTIVE_SESSION,
            self::SESSION_HORARIO_MISMATCH,
            self::INVALID_QR,
            self::EXPIRED_QR,
            self::ATTENDANCE_TIME_WINDOW_CLOSED,
            self::QR_CLASSROOM_MISMATCH,
            self::MISSING_REQUIRED_DATA,
            self::INVALID_DATA_FORMAT,
            self::ENROLLMENT_REQUEST_EXPIRED
                => 422,

            // 500 Internal Server Error
            self::DATABASE_ERROR,
            self::UNEXPECTED_ERROR
                => 500,

            // 503 Service Unavailable
            self::EXTERNAL_SERVICE_ERROR,
            self::SERVICE_UNAVAILABLE,
            self::CLASSROOM_NOT_AVAILABLE,
            self::CLASSROOM_IN_MAINTENANCE,
            self::RESOURCE_NOT_AVAILABLE,
            self::RESOURCE_IN_MAINTENANCE
                => 503,

            // 400 Bad Request (default)
            default => 400,
        };
    }

    /**
     * Obtener descripción legible del error
     *
     * @return string
     */
    public function description(): string
    {
        return match ($this) {
            self::UNAUTHORIZED => 'Usuario no autorizado para esta acción',
            self::INSUFFICIENT_ROLE => 'Rol insuficiente',
            self::INVALID_CREDENTIALS => 'Credenciales inválidas',
            self::TOKEN_EXPIRED => 'Token expirado',
            self::INVALID_TOKEN => 'Token inválido',
            self::NOT_ASSIGNED_TEACHER => 'No es el docente asignado',
            self::NOT_MANAGER => 'No es gestor del recurso',

            self::GROUP_FULL => 'Grupo lleno',
            self::ALREADY_ENROLLED => 'Ya inscrito',
            self::ALREADY_ENROLLED_IN_SUBJECT => 'Ya inscrito en esta materia',
            self::ENROLLMENT_CLOSED => 'Inscripciones cerradas',
            self::INACTIVE_CYCLE => 'Ciclo inactivo',
            self::ENROLLMENT_REQUEST_NOT_FOUND => 'Solicitud no encontrada',
            self::ENROLLMENT_REQUEST_ALREADY_PROCESSED => 'Solicitud ya procesada',
            self::ENROLLMENT_REQUEST_EXPIRED => 'Solicitud expirada',

            self::SCHEDULE_CONFLICT => 'Conflicto de horario',
            self::TEACHER_SCHEDULE_CONFLICT => 'Docente ocupado',
            self::STUDENT_SCHEDULE_CONFLICT => 'Estudiante con conflicto de horario',
            self::INVALID_TIME_RANGE => 'Rango de tiempo inválido',
            self::INVALID_DATE_RANGE => 'Rango de fecha inválido',

            self::CLASSROOM_NOT_AVAILABLE => 'Aula no disponible',
            self::CLASSROOM_IN_MAINTENANCE => 'Aula en mantenimiento',
            self::RESOURCE_NOT_AVAILABLE => 'Recurso no disponible',
            self::RESOURCE_IN_MAINTENANCE => 'Recurso en mantenimiento',
            self::CLASSROOM_CAPACITY_EXCEEDED => 'Capacidad excedida',

            self::SESSION_ALREADY_STARTED => 'Sesión ya iniciada',
            self::SESSION_NOT_ACTIVE => 'Sesión no activa',
            self::SESSION_ALREADY_ENDED => 'Sesión ya finalizada',
            self::CANNOT_END_INACTIVE_SESSION => 'No se puede finalizar sesión inactiva',
            self::SESSION_HORARIO_MISMATCH => 'Sesión no pertenece al horario',

            self::NO_ACTIVE_SESSION => 'No hay clase activa',
            self::STUDENT_NOT_ENROLLED => 'Estudiante no inscrito',
            self::INVALID_QR => 'QR inválido',
            self::EXPIRED_QR => 'QR expirado',
            self::ATTENDANCE_ALREADY_REGISTERED => 'Asistencia ya registrada',
            self::ATTENDANCE_TIME_WINDOW_CLOSED => 'Tiempo de registro cerrado',
            self::QR_CLASSROOM_MISMATCH => 'QR no coincide con aula',

            self::VALIDATION_ERROR => 'Error de validación',
            self::MISSING_REQUIRED_DATA => 'Datos requeridos faltantes',
            self::INVALID_DATA_FORMAT => 'Formato de datos inválido',

            self::NOT_FOUND => 'No encontrado',
            self::USER_NOT_FOUND => 'Usuario no encontrado',
            self::CLASSROOM_NOT_FOUND => 'Aula no encontrada',
            self::SCHEDULE_NOT_FOUND => 'Horario no encontrado',
            self::GROUP_NOT_FOUND => 'Grupo no encontrado',
            self::SUBJECT_NOT_FOUND => 'Materia no encontrada',

            self::DATABASE_ERROR => 'Error de base de datos',
            self::EXTERNAL_SERVICE_ERROR => 'Error de servicio externo',
            self::UNEXPECTED_ERROR => 'Error inesperado',
            self::SERVICE_UNAVAILABLE => 'Servicio no disponible',
        };
    }
}
