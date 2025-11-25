<?php

namespace App\Http\Controllers;

use App\RolesEnum;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DisponibilidadAulasController extends Controller
{
    /**
     * Consultar aulas disponibles según día y hora específicos
     */
    public function consultarDisponibilidad(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        // Validación de entrada
        $validator = Validator::make($request->all(), [
            'dia_semana' => 'required|in:Lunes,Martes,Miercoles,Jueves,Viernes,Sabado,Domingo',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
        ], [
            'dia_semana.required' => 'El día de la semana es obligatorio',
            'dia_semana.in' => 'El día debe ser uno válido',
            'hora_inicio.required' => 'La hora de inicio es obligatoria',
            'hora_inicio.date_format' => 'La hora de inicio debe tener formato HH:MM',
            'hora_fin.required' => 'La hora de fin es obligatoria',
            'hora_fin.date_format' => 'La hora de fin debe tener formato HH:MM',
            'hora_fin.after' => 'La hora de fin debe ser posterior a la hora de inicio',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $diaSemana = $request->dia_semana;
            $horaInicio = $request->hora_inicio;
            $horaFin = $request->hora_fin;

            Log::info('Buscando disponibilidad', [
                'dia' => $diaSemana,
                'hora_inicio' => $horaInicio,
                'hora_fin' => $horaFin
            ]);

            // Obtener IDs de aulas ocupadas en ese horario
            $aulasOcupadasIds = DB::table('horarios')
                ->where('dia_semana', $diaSemana)
                ->where(function ($query) use ($horaInicio, $horaFin) {
                    // Verificar solapamiento de horarios
                    $query->where(function ($q) use ($horaInicio, $horaFin) {
                        // Caso 1: El horario existente comienza dentro del rango buscado
                        $q->whereBetween('hora_inicio', [$horaInicio, $horaFin]);
                    })->orWhere(function ($q) use ($horaInicio, $horaFin) {
                        // Caso 2: El horario existente termina dentro del rango buscado
                        $q->whereBetween('hora_fin', [$horaInicio, $horaFin]);
                    })->orWhere(function ($q) use ($horaInicio, $horaFin) {
                        // Caso 3: El horario existente envuelve completamente el rango buscado
                        $q->where('hora_inicio', '<=', $horaInicio)
                          ->where('hora_fin', '>=', $horaFin);
                    });
                })
                ->pluck('aula_id')
                ->unique();

            Log::info('Aulas ocupadas encontradas', ['count' => $aulasOcupadasIds->count()]);

            // Obtener todas las aulas disponibles (que NO están en la lista de ocupadas)
            $aulasDisponibles = DB::table('aulas')
                ->whereNotIn('id', $aulasOcupadasIds)
                ->where('estado', '!=', 'inactiva')
                ->select(
                    'id',
                    'codigo',
                    'nombre',
                    'capacidad_pupitres',
                    'ubicacion',
                    'estado'
                )
                ->orderBy('nombre')
                ->get();

            // Enriquecer con información de recursos
            $aulasDisponibles = $aulasDisponibles->map(function ($aula) {
                $recursos = DB::table('aula_recursos')
                    ->join('recursos_tipos', 'aula_recursos.recurso_tipo_id', '=', 'recursos_tipos.id')
                    ->where('aula_recursos.aula_id', $aula->id)
                    ->where('aula_recursos.estado', '!=', 'malo') // Solo recursos funcionales
                    ->select('recursos_tipos.nombre', 'aula_recursos.cantidad', 'aula_recursos.estado')
                    ->get();
                
                $aula->recursos = $recursos;
                return $aula;
            });

            Log::info('Aulas disponibles encontradas', ['count' => $aulasDisponibles->count()]);

            if ($aulasDisponibles->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No hay aulas disponibles en el horario solicitado',
                    'data' => [],
                    'total' => 0
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Aulas disponibles obtenidas exitosamente',
                'data' => $aulasDisponibles,
                'total' => $aulasDisponibles->count(),
                'filtros_aplicados' => [
                    'dia_semana' => $diaSemana,
                    'hora_inicio' => $horaInicio,
                    'hora_fin' => $horaFin
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Error al consultar disponibilidad', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar disponibilidad de aulas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener historial personal de uso de aulas del docente autenticado
     */
    public function miHistorialAulas(): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $docenteId = Auth::id();

            Log::info('Obteniendo historial de aulas', ['docente_id' => $docenteId]);

            // Obtener historial de sesiones del docente
            $historial = DB::table('sesiones_clases')
                ->join('horarios', 'sesiones_clases.horario_id', '=', 'horarios.id')
                ->join('grupos', 'horarios.grupo_id', '=', 'grupos.id')
                ->join('aulas', 'horarios.aula_id', '=', 'aulas.id')
                ->join('materias', 'grupos.materia_id', '=', 'materias.id')
                ->join('ciclos_academicos', 'grupos.ciclo_id', '=', 'ciclos_academicos.id')
                ->where('grupos.docente_id', $docenteId)
                ->select(
                    'sesiones_clases.id as sesion_id',
                    'sesiones_clases.fecha_clase',
                    'sesiones_clases.hora_inicio_real',
                    'sesiones_clases.hora_fin_real',
                    'sesiones_clases.duracion_minutos',
                    'sesiones_clases.estado',
                    'sesiones_clases.retraso_minutos',
                    'aulas.id as aula_id',
                    'aulas.codigo as aula_codigo',
                    'aulas.nombre as aula_nombre',
                    'aulas.ubicacion as aula_ubicacion',
                    'materias.id as materia_id',
                    'materias.nombre as materia_nombre',
                    'materias.codigo as materia_codigo',
                    'grupos.numero_grupo',
                    'ciclos_academicos.nombre as ciclo_nombre',
                    'horarios.dia_semana',
                    'horarios.hora_inicio as hora_programada_inicio',
                    'horarios.hora_fin as hora_programada_fin'
                )
                ->orderBy('sesiones_clases.fecha_clase', 'desc')
                ->orderBy('horarios.hora_inicio', 'desc')
                ->get();

            if ($historial->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No tiene historial de uso de aulas registrado',
                    'data' => [],
                    'total' => 0,
                    'estadisticas' => [
                        'total_sesiones' => 0,
                        'sesiones_finalizadas' => 0,
                        'sesiones_canceladas' => 0,
                        'aulas_utilizadas' => 0,
                        'duracion_total_minutos' => 0,
                        'promedio_retraso_minutos' => 0
                    ]
                ], 200);
            }

            // Agrupar estadísticas
            $estadisticas = [
                'total_sesiones' => $historial->count(),
                'sesiones_finalizadas' => $historial->where('estado', 'finalizada')->count(),
                'sesiones_canceladas' => $historial->where('estado', 'cancelada')->count(),
                'aulas_utilizadas' => $historial->pluck('aula_id')->unique()->count(),
                'duracion_total_minutos' => $historial->sum('duracion_minutos'),
                'promedio_retraso_minutos' => round($historial->where('retraso_minutos', '>', 0)->avg('retraso_minutos') ?? 0, 2)
            ];

            return response()->json([
                'success' => true,
                'message' => 'Historial de aulas obtenido exitosamente',
                'data' => $historial,
                'estadisticas' => $estadisticas,
                'total' => $historial->count()
            ], 200);

        } catch (Exception $e) {
            Log::error('Error al obtener historial de aulas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial de aulas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener historial de uso de aulas con filtros opcionales
     */
    public function miHistorialConFiltros(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        // Validación opcional de filtros
        $validator = Validator::make($request->all(), [
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'aula_id' => 'nullable|exists:aulas,id',
            'estado' => 'nullable|in:programada,en_curso,finalizada,cancelada,sin_marcar_salida',
            'materia_id' => 'nullable|exists:materias,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $docenteId = Auth::id();

            $query = DB::table('sesiones_clases')
                ->join('horarios', 'sesiones_clases.horario_id', '=', 'horarios.id')
                ->join('grupos', 'horarios.grupo_id', '=', 'grupos.id')
                ->join('aulas', 'horarios.aula_id', '=', 'aulas.id')
                ->join('materias', 'grupos.materia_id', '=', 'materias.id')
                ->join('ciclos_academicos', 'grupos.ciclo_id', '=', 'ciclos_academicos.id')
                ->where('grupos.docente_id', $docenteId);

            // Aplicar filtros opcionales
            if ($request->has('fecha_inicio')) {
                $query->where('sesiones_clases.fecha_clase', '>=', $request->fecha_inicio);
            }

            if ($request->has('fecha_fin')) {
                $query->where('sesiones_clases.fecha_clase', '<=', $request->fecha_fin);
            }

            if ($request->has('aula_id')) {
                $query->where('horarios.aula_id', $request->aula_id);
            }

            if ($request->has('estado')) {
                $query->where('sesiones_clases.estado', $request->estado);
            }

            if ($request->has('materia_id')) {
                $query->where('grupos.materia_id', $request->materia_id);
            }

            $historial = $query->select(
                    'sesiones_clases.id as sesion_id',
                    'sesiones_clases.fecha_clase',
                    'sesiones_clases.hora_inicio_real',
                    'sesiones_clases.hora_fin_real',
                    'sesiones_clases.duracion_minutos',
                    'sesiones_clases.estado',
                    'sesiones_clases.retraso_minutos',
                    'sesiones_clases.observaciones',
                    'aulas.id as aula_id',
                    'aulas.codigo as aula_codigo',
                    'aulas.nombre as aula_nombre',
                    'aulas.ubicacion as aula_ubicacion',
                    'materias.id as materia_id',
                    'materias.nombre as materia_nombre',
                    'materias.codigo as materia_codigo',
                    'grupos.numero_grupo',
                    'ciclos_academicos.nombre as ciclo_nombre',
                    'horarios.dia_semana',
                    'horarios.hora_inicio as hora_programada_inicio',
                    'horarios.hora_fin as hora_programada_fin'
                )
                ->orderBy('sesiones_clases.fecha_clase', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Historial filtrado obtenido exitosamente',
                'data' => $historial,
                'total' => $historial->count(),
                'filtros_aplicados' => $request->only(['fecha_inicio', 'fecha_fin', 'aula_id', 'estado', 'materia_id'])
            ], 200);

        } catch (Exception $e) {
            Log::error('Error al obtener historial filtrado', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial filtrado',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}