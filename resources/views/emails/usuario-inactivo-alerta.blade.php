@extends('emails.layout')

@section('header-title', '⚠️ Alerta de Cuenta Inactiva')
@section('header-subtitle', 'Acción Requerida')

@section('content')
    <p class="greeting">Hola <strong>{{ $usuario->nombre_completo ?? 'Usuario' }}</strong>,</p>

    <div class="message">
        <p style="margin: 0;">
            <strong>{{ $mensaje }}</strong>
        </p>
    </div>

    <div class="alert error">
        <p style="margin: 0; color: #721c24;"><strong>⚠️ Tu cuenta será eliminada pronto</strong></p>
        <p style="margin: 8px 0 0 0; color: #721c24;">
            Tu cuenta ha estado inactiva por <strong>{{ $diasInactivo }} días</strong>.
            Si no inicias sesión en los próximos <strong>{{ $diasRestantes }} días</strong>,
            tu cuenta será eliminada automáticamente por inactividad.
        </p>
    </div>

    <h2>Información de tu Cuenta</h2>

    <table class="info-table">
        <tr>
            <td>Email:</td>
            <td>{{ $usuario->email ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td>Último acceso:</td>
            <td>{{ $ultimoLogin }}</td>
        </tr>
        <tr>
            <td>Días inactivo:</td>
            <td><strong style="color: #dc3545;">{{ $diasInactivo }} días</strong></td>
        </tr>
        <tr>
            <td>Días restantes:</td>
            <td><strong style="color: #ffc107;">{{ $diasRestantes }} días</strong></td>
        </tr>
        <tr>
            <td>Fecha de eliminación:</td>
            <td><strong>{{ $fechaEliminacion }}</strong></td>
        </tr>
    </table>

    <div class="divider"></div>

    <p>
        <strong>¿Cómo evitar la eliminación de tu cuenta?</strong>
    </p>
    <ul class="info-list">
        <li>Inicia sesión en el sistema antes de la fecha de eliminación</li>
        <li>Tu cuenta se reactivará automáticamente al iniciar sesión</li>
        <li>Todos tus datos se conservarán si accedes a tiempo</li>
    </ul>

    <p style="text-align: center; margin-top: 30px;">
        <a href="{{ config('app.url') }}/login" class="button">
            Iniciar Sesión Ahora
        </a>
    </p>

    <div class="alert" style="margin-top: 30px;">
        <p style="margin: 0;"><strong>💡 Importante</strong></p>
        <p style="margin: 8px 0 0 0;">
            Si ya no necesitas acceso al sistema, puedes ignorar este mensaje y tu cuenta
            será eliminada automáticamente. Todos tus datos personales serán removidos
            de forma permanente.
        </p>
    </div>

    <p style="color: #888888; font-size: 14px; margin-top: 30px;">
        Esta es una medida de seguridad para mantener el sistema limpio y proteger la privacidad
        de los usuarios inactivos.
    </p>
@endsection
