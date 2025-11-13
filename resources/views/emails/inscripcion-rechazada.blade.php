@extends('emails.layout')

@section('header-title', 'Solicitud de Inscripción Rechazada')
@section('header-subtitle', 'Información Importante')

@section('content')
    <p class="greeting">Hola <strong>{{ $usuario->nombre_completo ?? 'Estudiante' }}</strong>,</p>

    <div class="message">
        <p style="margin: 0;">
            {{ $mensaje }}
        </p>
    </div>

    <div class="alert error">
        <p style="margin: 0; color: #721c24;"><strong>❌ Solicitud No Aprobada</strong></p>
        <p style="margin: 8px 0 0 0; color: #721c24;">
            Lamentablemente, tu solicitud de inscripción no fue aprobada en este momento.
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
        @if($motivoRechazo)
            <tr>
                <td>Motivo:</td>
                <td>{{ $motivoRechazo }}</td>
            </tr>
        @endif
    </table>

    <div class="divider"></div>

    <p>
        <strong>¿Qué puedes hacer ahora?</strong>
    </p>
    <ul class="info-list">
        <li>Contacta al docente o coordinador académico para obtener más información</li>
        <li>Explora otros grupos disponibles de la misma materia</li>
        <li>Consulta con tu asesor académico sobre alternativas</li>
    </ul>

    <p style="text-align: center; margin-top: 30px;">
        <a href="{{ config('app.url') }}/grupos-disponibles" class="button">
            Ver Grupos Disponibles
        </a>
    </p>

    <p style="color: #888888; font-size: 14px; margin-top: 30px;">
        Si crees que esto es un error, por favor contacta a tu coordinador académico.
    </p>
@endsection
