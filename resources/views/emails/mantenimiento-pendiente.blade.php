@extends('emails.layout')

@section('header-title', 'Reporte de Mantenimientos Pendientes')
@section('header-subtitle', 'Reporte Semanal')

@section('content')
    <p class="greeting">Hola <strong>{{ $usuario->nombre_completo ?? 'Gestor' }}</strong>,</p>

    <div class="message">
        <p style="margin: 0;">
            {{ $mensaje }}
        </p>
    </div>

    @if($totalPendientes > 0)
        <div class="alert">
            <p style="margin: 0;"><strong>🔧 Mantenimientos Pendientes: {{ $totalPendientes }}</strong></p>
            <p style="margin: 8px 0 0 0;">
                Se han identificado {{ $totalPendientes }} mantenimiento(s) pendiente(s) en tu departamento.
            </p>
        </div>
    @else
        <div class="alert success">
            <p style="margin: 0; color: #0c5460;"><strong>✅ Todo en Orden</strong></p>
            <p style="margin: 8px 0 0 0; color: #0c5460;">
                No hay mantenimientos pendientes en tu departamento. ¡Excelente trabajo!
            </p>
        </div>
    @endif

    <h2>Departamento: {{ $departamentoNombre }}</h2>

    @if(!empty($mantenimientos) && count($mantenimientos) > 0)
        <table class="info-table" style="margin-top: 20px;">
            <thead>
                <tr style="background-color: #f9f9fb;">
                    <th style="padding: 12px; text-align: left; color: #5B0B0B;">Aula</th>
                    <th style="padding: 12px; text-align: left; color: #5B0B0B;">Recurso</th>
                    <th style="padding: 12px; text-align: left; color: #5B0B0B;">Prioridad</th>
                </tr>
            </thead>
            <tbody>
                @foreach($mantenimientos as $mantenimiento)
                    <tr>
                        <td style="padding: 12px;">{{ $mantenimiento['aula_nombre'] ?? 'N/A' }}</td>
                        <td style="padding: 12px;">{{ $mantenimiento['recurso_tipo'] ?? 'N/A' }}</td>
                        <td style="padding: 12px;">
                            @if(($mantenimiento['prioridad'] ?? 'media') === 'alta')
                                <span style="color: #dc3545; font-weight: 600;">🔴 Alta</span>
                            @elseif(($mantenimiento['prioridad'] ?? 'media') === 'media')
                                <span style="color: #ffc107; font-weight: 600;">🟡 Media</span>
                            @else
                                <span style="color: #28a745; font-weight: 600;">🟢 Baja</span>
                            @endif
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e0e0e0;">
                        <td colspan="3" style="padding: 8px 12px; font-size: 14px; color: #666;">
                            {{ $mantenimiento['descripcion'] ?? 'Sin descripción' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p style="text-align: center; margin-top: 30px;">
            <a href="{{ config('app.url') }}/mantenimientos" class="button">
                Gestionar Mantenimientos
            </a>
        </p>
    @else
        <p style="margin-top: 20px; color: #666;">
            No se encontraron mantenimientos pendientes para tu departamento.
        </p>
    @endif

    <div class="divider"></div>

    <p style="color: #888888; font-size: 14px;">
        Este reporte se envía automáticamente todos los viernes a las 9:00 AM.
    </p>
@endsection
