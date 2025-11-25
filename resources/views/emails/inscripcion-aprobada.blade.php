@extends('emails.layout')

@section('header-title', '¡Inscripción Aprobada!')
@section('header-subtitle', 'Felicitaciones')

@section('content')
    <p class="greeting">Hola <strong>{{ $usuario->nombre_completo ?? 'Estudiante' }}</strong>,</p>

    <div class="message">
        <p style="margin: 0;">
            <strong>{{ $mensaje }}</strong>
        </p>
    </div>

    <div class="alert success">
        <p style="margin: 0; color: #0c5460;"><strong>✅ ¡Buenas noticias!</strong></p>
        <p style="margin: 8px 0 0 0; color: #0c5460;">
            Tu solicitud de inscripción ha sido aprobada. Ya puedes asistir a las clases.
        </p>
    </div>

    <h2>Detalles del Grupo</h2>

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
            <td>Docente:</td>
            <td>{{ $docenteNombre }}</td>
        </tr>
    </table>

    @if(!empty($horarios))
        <h2 style="margin-top: 30px;">Horarios de Clase</h2>
        <ul class="info-list">
            @foreach($horarios as $horario)
                <li>
                    <strong>{{ $horario['dia'] ?? 'Día' }}</strong>:
                    {{ $horario['hora_inicio'] ?? '00:00' }} - {{ $horario['hora_fin'] ?? '00:00' }}
                    (Aula: {{ $horario['aula'] ?? 'N/A' }})
                </li>
            @endforeach
        </ul>
    @endif

    <div class="divider"></div>

    <p>
        <strong>Próximos pasos:</strong>
    </p>
    <ul class="info-list">
        <li>Revisa los horarios de clase</li>
        <li>Asegúrate de llegar puntualmente al aula</li>
        <li>Recuerda registrar tu asistencia escaneando el código QR del aula</li>
    </ul>

    <p style="text-align: center; margin-top: 30px;">
        <a href="{{ config('app.url') }}/mis-inscripciones" class="button">
            Ver Mis Inscripciones
        </a>
    </p>
@endsection
