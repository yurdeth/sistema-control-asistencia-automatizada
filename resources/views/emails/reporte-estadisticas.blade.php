@extends('emails.layout')

@section('header-title', 'Reporte de Estadísticas')
@section('header-subtitle', 'Reporte {{ ucfirst($periodo ?? "Periódico") }}')

@section('content')
    <p class="greeting">Hola <strong>{{ $usuario->nombre_completo ?? 'Gestor' }}</strong>,</p>

    <div class="message">
        <p style="margin: 0;">
            {{ $mensaje }}
        </p>
    </div>

    <h2>Período del Reporte</h2>

    <table class="info-table">
        <tr>
            <td>Tipo de reporte:</td>
            <td><strong>{{ ucfirst($periodo ?? 'Diario') }}</strong></td>
        </tr>
        <tr>
            <td>Fecha inicio:</td>
            <td>{{ $fechaInicio }}</td>
        </tr>
        <tr>
            <td>Fecha fin:</td>
            <td>{{ $fechaFin }}</td>
        </tr>
    </table>

    @if(!empty($estadisticas))
        <h2 style="margin-top: 30px;">Resumen de Estadísticas</h2>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 20px;">
            <!-- Tarjeta: Total Sesiones -->
            @if(isset($estadisticas['total_sesiones']))
                <div style="background-color: #f9f9fb; padding: 20px; border-radius: 8px; border-left: 4px solid #5B0B0B;">
                    <p style="margin: 0; font-size: 14px; color: #888;">Sesiones Realizadas</p>
                    <p style="margin: 8px 0 0 0; font-size: 32px; font-weight: 700; color: #5B0B0B;">
                        {{ $estadisticas['total_sesiones'] }}
                    </p>
                </div>
            @endif

            <!-- Tarjeta: Total Asistencias -->
            @if(isset($estadisticas['total_asistencias']))
                <div style="background-color: #f9f9fb; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745;">
                    <p style="margin: 0; font-size: 14px; color: #888;">Asistencias Registradas</p>
                    <p style="margin: 8px 0 0 0; font-size: 32px; font-weight: 700; color: #28a745;">
                        {{ $estadisticas['total_asistencias'] }}
                    </p>
                </div>
            @endif
        </div>

        <!-- Promedio de Asistencia -->
        @if(isset($estadisticas['promedio_asistencia']))
            <div style="margin-top: 20px; background-color: #e8f4f8; padding: 20px; border-radius: 8px; border-left: 4px solid #17a2b8; text-align: center;">
                <p style="margin: 0; font-size: 14px; color: #0c5460;">Promedio de Asistencia</p>
                <p style="margin: 8px 0 0 0; font-size: 48px; font-weight: 700; color: #17a2b8;">
                    {{ number_format($estadisticas['promedio_asistencia'], 2) }}%
                </p>
            </div>
        @endif

        <!-- Aulas Más Usadas -->
        @if(isset($estadisticas['aulas_mas_usadas']) && !empty($estadisticas['aulas_mas_usadas']))
            <h2 style="margin-top: 30px;">Top 5 Aulas Más Utilizadas</h2>
            <table class="info-table">
                <thead>
                    <tr style="background-color: #f9f9fb;">
                        <th style="padding: 12px; text-align: left; color: #5B0B0B;">#</th>
                        <th style="padding: 12px; text-align: left; color: #5B0B0B;">Aula</th>
                        <th style="padding: 12px; text-align: left; color: #5B0B0B;">Sesiones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(array_slice($estadisticas['aulas_mas_usadas'], 0, 5) as $index => $aula)
                        <tr>
                            <td style="padding: 12px;">{{ $index + 1 }}</td>
                            <td style="padding: 12px;">{{ $aula['nombre'] ?? 'N/A' }}</td>
                            <td style="padding: 12px;"><strong>{{ $aula['sesiones'] ?? 0 }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- Materias con Mejor Asistencia -->
        @if(isset($estadisticas['materias_mejor_asistencia']) && !empty($estadisticas['materias_mejor_asistencia']))
            <h2 style="margin-top: 30px;">Top 5 Materias con Mejor Asistencia</h2>
            <table class="info-table">
                <thead>
                    <tr style="background-color: #f9f9fb;">
                        <th style="padding: 12px; text-align: left; color: #5B0B0B;">#</th>
                        <th style="padding: 12px; text-align: left; color: #5B0B0B;">Materia</th>
                        <th style="padding: 12px; text-align: left; color: #5B0B0B;">% Asistencia</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(array_slice($estadisticas['materias_mejor_asistencia'], 0, 5) as $index => $materia)
                        <tr>
                            <td style="padding: 12px;">{{ $index + 1 }}</td>
                            <td style="padding: 12px;">{{ $materia['nombre'] ?? 'N/A' }}</td>
                            <td style="padding: 12px;">
                                <strong style="color: #28a745;">{{ number_format($materia['porcentaje'] ?? 0, 2) }}%</strong>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @else
        <div class="alert">
            <p style="margin: 0;"><strong>ℹ️ Sin Datos Disponibles</strong></p>
            <p style="margin: 8px 0 0 0;">
                No hay datos estadísticos disponibles para el período seleccionado.
            </p>
        </div>
    @endif

    <p style="text-align: center; margin-top: 40px;">
        <a href="{{ config('app.url') }}/estadisticas" class="button">
            Ver Estadísticas Completas
        </a>
    </p>

    <div class="divider"></div>

    <p style="color: #888888; font-size: 14px;">
        Este reporte se genera automáticamente según la configuración del sistema.
        Si el reporte incluye un archivo adjunto, descárgalo para obtener más detalles.
    </p>
@endsection
