@extends('emails.layout')

@section('header-title', '⚠️ Alerta de Integridad de Datos')
@section('header-subtitle', 'Validación Semanal')

@section('content')
    <p class="greeting">Hola <strong>{{ $usuario->nombre_completo ?? 'Administrador' }}</strong>,</p>

    <div class="message">
        <p style="margin: 0;">
            {{ $mensaje }}
        </p>
    </div>

    @if($totalInconsistencias > 0)
        <div class="alert error">
            <p style="margin: 0; color: #721c24;"><strong>⚠️ Inconsistencias Detectadas: {{ $totalInconsistencias }}</strong></p>
            <p style="margin: 8px 0 0 0; color: #721c24;">
                Se encontraron inconsistencias en la base de datos que requieren atención.
            </p>
        </div>
    @else
        <div class="alert success">
            <p style="margin: 0; color: #0c5460;"><strong>✅ Base de Datos Íntegra</strong></p>
            <p style="margin: 8px 0 0 0; color: #0c5460;">
                No se detectaron inconsistencias. El sistema está funcionando correctamente.
            </p>
        </div>
    @endif

    <h2>Reporte de Validación</h2>

    <table class="info-table">
        <tr>
            <td>Fecha de validación:</td>
            <td>{{ $fechaValidacion }}</td>
        </tr>
        <tr>
            <td>Inconsistencias encontradas:</td>
            <td><strong style="color: {{ $totalInconsistencias > 0 ? '#dc3545' : '#28a745' }};">
                {{ $totalInconsistencias }}
            </strong></td>
        </tr>
    </table>

    @if(!empty($inconsistencias) && count($inconsistencias) > 0)
        <h2 style="margin-top: 30px;">Detalles de Inconsistencias</h2>

        @foreach($inconsistencias as $inconsistencia)
            <div style="margin-bottom: 20px; padding: 16px; background-color: #f9f9fb; border-left: 4px solid
                @if(($inconsistencia['severidad'] ?? 'media') === 'critica') #dc3545
                @elseif(($inconsistencia['severidad'] ?? 'media') === 'alta') #ff6b6b
                @elseif(($inconsistencia['severidad'] ?? 'media') === 'media') #ffc107
                @else #28a745
                @endif; border-radius: 4px;">

                <p style="margin: 0; font-weight: 600; color: #333;">
                    {{ ucfirst($inconsistencia['severidad'] ?? 'Media') }}: {{ $inconsistencia['tipo'] ?? 'Desconocido' }}
                </p>
                <p style="margin: 8px 0 0 0; color: #666; font-size: 14px;">
                    <strong>Tabla:</strong> {{ $inconsistencia['tabla'] ?? 'N/A' }}
                </p>
                <p style="margin: 4px 0 0 0; color: #666; font-size: 14px;">
                    <strong>Registros afectados:</strong> {{ $inconsistencia['registros_afectados'] ?? 0 }}
                </p>
                <p style="margin: 8px 0 0 0; color: #555;">
                    {{ $inconsistencia['descripcion'] ?? 'Sin descripción' }}
                </p>
            </div>
        @endforeach

        <p style="text-align: center; margin-top: 30px;">

{{--            Todas las de este tipo solo son rutas de prueba, no son oficiales, después se cambian--}}
            <a href="{{ config('app.url') }}/admin/integridad-datos" class="button">
                Ver Reporte Completo

            </a>
        </p>
    @endif

    <div class="divider"></div>

    <p style="color: #888888; font-size: 14px;">
        Este reporte se genera automáticamente todos los domingos a las 3:00 AM.
        Solo se envía si se detectan inconsistencias que requieren atención.
    </p>
@endsection
