@extends('emails.layout')

@section('header-title', 'Nueva Solicitud de Inscripción')
@section('header-subtitle', 'Acción Requerida')

@section('content')
    <p class="greeting">Hola <strong>{{ $usuario->nombre_completo ?? 'Docente' }}</strong>,</p>

    <div class="message">
        <p style="margin: 0;">
            <strong>{{ $mensaje }}</strong>
        </p>
    </div>

    <h2>Detalles de la Solicitud</h2>

    <table class="info-table">
        <tr>
            <td>Estudiante:</td>
            <td><strong>{{ $estudianteNombre }}</strong></td>
        </tr>
        <tr>
            <td>Email:</td>
            <td>{{ $estudianteEmail }}</td>
        </tr>
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
    </table>

    <div class="alert">
        <p style="margin: 0;"><strong>⏰ Importante</strong></p>
        <p style="margin: 8px 0 0 0;">
            Por favor revisa esta solicitud en los próximos días. Si no respondes en 30 días, la solicitud expirará automáticamente.
        </p>
    </div>

    <p style="text-align: center; margin-top: 30px;">
        <a href="{{ config('app.url') }}/solicitudes-inscripcion" class="button">
            Ver Solicitud en el Sistema
        </a>
    </p>

    <div class="divider"></div>

    <p style="color: #888888; font-size: 14px;">
        Recibiste este correo porque eres el docente responsable de este grupo.
    </p>
@endsection
