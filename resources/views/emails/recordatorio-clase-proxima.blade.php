@extends('emails.layout')

@section('header-title', '¡Clase Próxima!')
@section('header-subtitle', 'Recordatorio de Clase')

@section('content')
    <p class="greeting">Hola <strong>{{ $usuario->nombre_completo ?? 'Usuario' }}</strong>,</p>

    <div class="message">
        <p style="margin: 0;">
            <strong>{{ $mensaje }}</strong>
        </p>
    </div>

    <h2>Detalles de la Clase</h2>

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
            <td>Aula:</td>
            <td>{{ $aulaNombre }}</td>
        </tr>
        <tr>
            <td>Horario:</td>
            <td>{{ $horaInicio }} - {{ $horaFin }}</td>
        </tr>
        <tr>
            <td>Tiempo restante:</td>
            <td><strong style="color: #5B0B0B;">{{ $minutosRestantes }} minutos</strong></td>
        </tr>
    </table>

    @if($minutosRestantes <= 15)
        <div class="alert error">
            <p style="margin: 0;"><strong>⚠️ ¡La clase inicia muy pronto!</strong></p>
            <p style="margin: 8px 0 0 0;">Dirígete al aula lo antes posible para no llegar tarde.</p>
        </div>
    @else
        <div class="alert">
            <p style="margin: 0;"><strong>📅 Recuerda</strong></p>
            <p style="margin: 8px 0 0 0;">Prepara tus materiales y dirígete al aula con anticipación.</p>
        </div>
    @endif

    <div class="divider"></div>

    <p style="color: #888888; font-size: 14px;">
        Este es un recordatorio automático. Recibiste este correo porque estás registrado en este grupo.
    </p>
@endsection
