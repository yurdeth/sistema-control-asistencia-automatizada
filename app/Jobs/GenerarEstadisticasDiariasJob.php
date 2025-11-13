<?php

namespace App\Jobs;

use App\Models\estadisticas_aulas_diarias;
use App\Models\aulas;
use App\Models\sesiones_clase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Job para Generar Estadísticas Diarias
 *
 * Este Job genera estadísticas diarias de uso de aulas y asistencias.
 * Se ejecuta de forma nocturna (recomendado: 2:00 AM) para no afectar el rendimiento diurno.
 *
 * REEMPLAZA:
 * - evt_generar_estadisticas_diarias (evento SQL)
 * - sp_generar_estadisticas_diarias (stored procedure SQL)
 *
 * FUNCIONALIDADES:
 * 1. Genera estadísticas de uso de aulas por día
 * 2. Calcula métricas de asistencia por grupo/materia
 * 3. Identifica tendencias de uso de recursos
 * 4. Detecta aulas subutilizadas o sobreutilizadas
 *
 * PROGRAMACIÓN:
 * - Scheduler: Diario a las 2:00 AM
 * - Fecha: Procesa estadísticas del día anterior
 *
 * @package App\Jobs
 */
class GenerarEstadisticasDiariasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Fecha para la cual se generan las estadísticas
     */
    protected Carbon $fecha;

    /**
     * Número de intentos permitidos
     *
     * @var int
     */
    public $tries = 2;

    /**
     * Tiempo máximo de ejecución (segundos)
     *
     * @var int
     */
    public $timeout = 600; // 10 minutos para procesar estadísticas pesadas

    /**
     * Constructor
     *
     * @param Carbon|null $fecha Fecha a procesar (default: ayer)
     */
    public function __construct(?Carbon $fecha = null)
    {
        $this->fecha = $fecha ?? now()->subDay();
    }

    /**
     * Ejecuta el job
     *
     * @return void
     */
    public function handle(): void
    {
        $fechaStr = $this->fecha->toDateString();

        try {
            Log::info('Iniciando generación de estadísticas diarias', [
                'fecha' => $fechaStr,
                'timestamp' => now()->toIso8601String(),
            ]);

            DB::beginTransaction();

            // 1. Generar estadísticas de aulas
            $statsAulas = $this->generarEstadisticasAulas();

            // 2. Calcular métricas de asistencia
            $statsAsistencia = $this->calcularMetricasAsistencia();

            // 3. Detectar anomalías (opcional)
            $anomalias = $this->detectarAnomalias($statsAulas);

            DB::commit();

            Log::info('Estadísticas diarias generadas exitosamente', [
                'fecha' => $fechaStr,
                'aulas_procesadas' => count($statsAulas),
                'grupos_procesados' => count($statsAsistencia),
                'anomalias_detectadas' => count($anomalias),
                'duracion_segundos' => round(microtime(true) - LARAVEL_START, 2),
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Error al generar estadísticas diarias', [
                'fecha' => $fechaStr,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Genera estadísticas de uso de aulas para la fecha especificada
     *
     * @return array Estadísticas generadas
     */
    protected function generarEstadisticasAulas(): array
    {
        $fechaStr = $this->fecha->toDateString();
        $stats = [];

        // Obtener todas las aulas activas
        $aulas = aulas::where('estado', '!=', 'inactivo')->get();

        foreach ($aulas as $aula) {
            // Contar sesiones realizadas en esta aula para la fecha
            $sesionesRealizadas = sesiones_clase::whereHas('horario', function ($query) use ($aula) {
                    $query->where('aula_id', $aula->id);
                })
                ->whereDate('fecha_clase', $fechaStr)
                ->where('estado', 'finalizada')
                ->count();

            // Calcular tiempo total de uso (suma de duraciones)
            $tiempoUsoMinutos = sesiones_clase::whereHas('horario', function ($query) use ($aula) {
                    $query->where('aula_id', $aula->id);
                })
                ->whereDate('fecha_clase', $fechaStr)
                ->where('estado', 'finalizada')
                ->sum('duracion_minutos') ?? 0;

            // Contar estudiantes únicos que usaron el aula
            $estudiantesUnicos = DB::table('asistencias_estudiantes')
                ->join('sesiones_clase', 'asistencias_estudiantes.sesion_clase_id', '=', 'sesiones_clase.id')
                ->join('horarios', 'sesiones_clase.horario_id', '=', 'horarios.id')
                ->where('horarios.aula_id', $aula->id)
                ->whereDate('sesiones_clase.fecha_clase', $fechaStr)
                ->distinct('asistencias_estudiantes.estudiante_id')
                ->count();

            // Calcular tasa de ocupación (basado en horario de 8 AM a 9 PM = 13 horas = 780 minutos)
            $minutosDisponibles = 780; // 13 horas
            $tasaOcupacion = $minutosDisponibles > 0
                ? round(($tiempoUsoMinutos / $minutosDisponibles) * 100, 2)
                : 0;

            // Crear o actualizar estadística
            $estadistica = estadisticas_aulas_diarias::updateOrCreate(
                [
                    'aula_id' => $aula->id,
                    'fecha' => $fechaStr,
                ],
                [
                    'sesiones_realizadas' => $sesionesRealizadas,
                    'tiempo_uso_minutos' => $tiempoUsoMinutos,
                    'estudiantes_unicos' => $estudiantesUnicos,
                    'tasa_ocupacion' => $tasaOcupacion,
                ]
            );

            $stats[] = [
                'aula_id' => $aula->id,
                'aula_nombre' => $aula->nombre,
                'sesiones' => $sesionesRealizadas,
                'minutos' => $tiempoUsoMinutos,
                'estudiantes' => $estudiantesUnicos,
                'ocupacion' => $tasaOcupacion,
            ];
        }

        return $stats;
    }

    /**
     * Calcula métricas de asistencia por grupo y materia
     *
     * @return array Métricas calculadas
     */
    protected function calcularMetricasAsistencia(): array
    {
        $fechaStr = $this->fecha->toDateString();
        $metricas = [];

        // Obtener todas las sesiones del día
        $sesiones = sesiones_clase::with(['horario.grupo.materia', 'asistencias'])
            ->whereDate('fecha_clase', $fechaStr)
            ->where('estado', 'finalizada')
            ->get();

        foreach ($sesiones as $sesion) {
            $grupo = $sesion->horario->grupo;
            $totalInscritos = $grupo->estudiantes_inscritos ?? 0;
            $totalAsistencias = $sesion->asistencias->count();
            $porcentajeAsistencia = $totalInscritos > 0
                ? round(($totalAsistencias / $totalInscritos) * 100, 2)
                : 0;

            $metricas[] = [
                'grupo_id' => $grupo->id,
                'materia_nombre' => $grupo->materia->nombre ?? 'N/A',
                'inscritos' => $totalInscritos,
                'asistencias' => $totalAsistencias,
                'porcentaje' => $porcentajeAsistencia,
                'fecha' => $fechaStr,
            ];
        }

        return $metricas;
    }

    /**
     * Detecta anomalías en el uso de aulas
     *
     * Identifica:
     * - Aulas con ocupación < 20% (subutilizadas)
     * - Aulas con ocupación > 90% (sobreutilizadas)
     * - Aulas sin uso en días laborables
     *
     * @param array $statsAulas Estadísticas de aulas generadas
     * @return array Anomalías detectadas
     */
    protected function detectarAnomalias(array $statsAulas): array
    {
        $anomalias = [];

        // Solo revisar anomalías en días laborables (lunes a viernes)
        $diaSemana = $this->fecha->dayOfWeek;
        if ($diaSemana === Carbon::SATURDAY || $diaSemana === Carbon::SUNDAY) {
            return $anomalias; // No detectar anomalías en fin de semana
        }

        foreach ($statsAulas as $stat) {
            // Aula subutilizada
            if ($stat['ocupacion'] > 0 && $stat['ocupacion'] < 20) {
                $anomalias[] = [
                    'tipo' => 'subutilizada',
                    'aula_id' => $stat['aula_id'],
                    'aula_nombre' => $stat['aula_nombre'],
                    'ocupacion' => $stat['ocupacion'],
                    'mensaje' => "Aula '{$stat['aula_nombre']}' subutilizada con solo {$stat['ocupacion']}% de ocupación",
                ];
            }

            // Aula sobreutilizada
            if ($stat['ocupacion'] > 90) {
                $anomalias[] = [
                    'tipo' => 'sobreutilizada',
                    'aula_id' => $stat['aula_id'],
                    'aula_nombre' => $stat['aula_nombre'],
                    'ocupacion' => $stat['ocupacion'],
                    'mensaje' => "Aula '{$stat['aula_nombre']}' sobreutilizada con {$stat['ocupacion']}% de ocupación",
                ];
            }

            // Aula sin uso (estado disponible pero no utilizada)
            if ($stat['sesiones'] === 0) {
                $anomalias[] = [
                    'tipo' => 'sin_uso',
                    'aula_id' => $stat['aula_id'],
                    'aula_nombre' => $stat['aula_nombre'],
                    'mensaje' => "Aula '{$stat['aula_nombre']}' sin uso el día {$this->fecha->toDateString()}",
                ];
            }
        }

        // Loguear anomalías detectadas
        if (!empty($anomalias)) {
            Log::warning('Anomalías detectadas en uso de aulas', [
                'fecha' => $this->fecha->toDateString(),
                'total_anomalias' => count($anomalias),
                'anomalias' => $anomalias,
            ]);
        }

        return $anomalias;
    }

    /**
     * Maneja el fallo del job
     *
     * @param Exception $exception
     * @return void
     */
    public function failed(Exception $exception): void
    {
        Log::critical('Job de estadísticas diarias falló', [
            'fecha' => $this->fecha->toDateString(),
            'error' => $exception->getMessage(),
        ]);
    }
}
