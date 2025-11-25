@extends('emails.layout')

@section('header-title', 'Solicitud de Inscripción Expirada')
@section('header-subtitle', 'Información')

@section('content')
    <p class="greeting">Hola <strong>{{ $usuario->nombre_completo ?? 'Estudiante' }}</strong>,</p>

    <div class="message">
        <p style="margin: 0;">
            {{ $mensaje }}
        </p>
    </div>

    <div class="alert">
        <p style="margin: 0;"><strong>⏰ Solicitud Expirada</strong></p>
        <p style="margin: 8px 0 0 0;">
            Tu solicitud de inscripción expiró automáticamente después de {{ $diasTranscurridos }} días sin recibir respuesta del docente.
        </p>
    </div>

    <h2>Detalles de la Solicitud</h2>

    <table class="info-table">
        <tr>
            <td>Materia:</td>
            <td><strong>{{ $materiaNombre }}</strong></td>
        </tr>
        <tr>
            <td>Grupo:</td>
            <td>{{ $grupoNombre }}</td>
        </tr>
        <tr>
            <td>Fecha de solicitud:</td>
            <td>{{ $fechaSolicitud }}</td>
        </tr>
        <tr>
            <td>Días transcurridos:</td>
            <td>{{ $diasTranscurridos }} días</td>
        </tr>
    </table>

    <div class="divider"></div>

    <p>
        <strong>¿Qué puedes hacer ahora?</strong>
    </p>
    <ul class="info-list">
        <li>Puedes enviar una nueva solicitud si el grupo sigue disponible</li>
        <li>Contacta directamente al docente o coordinador para consultar sobre el grupo</li>
        <li>Explora otros grupos disponibles de la misma materia</li>
    </ul>

    <p style="text-align: center; margin-top: 30px;">
        <a href="{{ config('app.url') }}/grupos-disponibles" class="button">
            Ver Grupos Disponibles
        </a>
    </p>

    <p style="color: #888888; font-size: 14px; margin-top: 30px;">
        Las solicitudes expiran automáticamente después de 30 días para mantener actualizado el sistema.
    </p>
@endsection
