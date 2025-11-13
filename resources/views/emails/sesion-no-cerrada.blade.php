@extends('emails.layout')

@section('header-title', '⚠️ Alerta: Sesión No Cerrada')
@section('header-subtitle', 'Acción Requerida')

@section('content')
    <p class="greeting">Hola <strong>{{ $usuario->nombre_completo ?? 'Docente' }}</strong>,</p>

    <div class="message">
        <p style="margin: 0;">
            <strong>{{ $mensaje }}</strong>
        </p>
    </div>

    <div class="alert error">
        <p style="margin: 0; color: #721c24;"><strong>⚠️ Sesión de Clase No Finalizada</strong></p>
        <p style="margin: 8px 0 0 0; color: #721c24;">
            Detectamos que una sesión de clase no fue cerrada correctamente. Por favor, ciérrala lo antes posible.
        </p>
    </div>

    <h2>Detalles de la Sesión</h2>

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
            <td>Fecha:</td>
            <td>{{ $fechaClase }}</td>
        </tr>
        <tr>
            <td>Hora de inicio:</td>
            <td>{{ $horaInicioReal }}</td>
        </tr>
        <tr>
            <td>Tiempo transcurrido:</td>
            <td><strong style="color: #dc3545;">{{ $tiempoTranscurrido }} minutos</strong></td>
        </tr>
    </table>

    <div class="divider"></div>

    <p>
        <strong>¿Por qué es importante cerrar la sesión?</strong>
    </p>
    <ul class="info-list">
        <li>Libera el aula para otros grupos</li>
        <li>Permite registrar la duración real de la clase</li>
        <li>Facilita el cálculo de estadísticas precisas</li>
        <li>Mantiene actualizado el sistema de asistencia</li>
    </ul>

    <p style="text-align: center; margin-top: 30px;">
        <a href="{{ config('app.url') }}/sesiones-activas" class="button">
            Cerrar Sesión Ahora
        </a>
    </p>

    <p style="color: #888888; font-size: 14px; margin-top: 30px;">
        Recuerda cerrar siempre tus sesiones al finalizar la clase para mantener el sistema actualizado.
    </p>
@endsection
