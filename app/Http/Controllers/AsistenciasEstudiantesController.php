<?php

namespace App\Http\Controllers;

use App\Http\Requests\Storeasistencias_estudiantesRequest;
use App\Http\Requests\Updateasistencias_estudiantesRequest;
use App\Models\asistencias_estudiantes;

class AsistenciasEstudiantesController extends Controller
{
     public function index()
    {
        try {
            $asistencias = asistencias_estudiantes::with(['sesionClase', 'estudiante'])->get();
            return response()->json([
                'success' => true,
                'data' => $asistencias
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las asistencias',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $asistencia = asistencias_estudiantes::with(['sesionClase', 'estudiante'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $asistencia
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Asistencia no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'sesion_clase_id' => 'required|exists:sesiones_clase,id',
                'estudiante_id' => 'required|exists:usuarios,id',
                'estado' => 'required|in:presente,tarde,ausente',
                'validado_por_qr' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $asistencia = asistencias_estudiantes::create([
                'sesion_clase_id' => $request->sesion_clase_id,
                'estudiante_id' => $request->estudiante_id,
                'hora_registro' => Carbon::now(),
                'estado' => $request->estado,
                'validado_por_qr' => $request->validado_por_qr ?? false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Asistencia registrada exitosamente',
                'data' => $asistencia
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la asistencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function edit(Request $request, $id)
    {
        try {
            $asistencia = asistencias_estudiantes::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'sesion_clase_id' => 'sometimes|exists:sesiones_clase,id',
                'estudiante_id' => 'sometimes|exists:usuarios,id',
                'estado' => 'sometimes|in:presente,tarde,ausente',
                'validado_por_qr' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $asistencia->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Asistencia actualizada exitosamente',
                'data' => $asistencia
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la asistencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $asistencia = asistencias_estudiantes::findOrFail($id);
            $asistencia->delete();

            return response()->json([
                'success' => true,
                'message' => 'Asistencia eliminada exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la asistencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getBySession($id)
    {
        try {
            $asistencias = asistencias_estudiantes::with(['estudiante'])
                ->where('sesion_clase_id', $id)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $asistencias
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las asistencias de la sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByStudent($id)
    {
        try {
            $asistencias = asistencias_estudiantes::with(['sesionClase.horario.grupo'])
                ->where('estudiante_id', $id)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $asistencias
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las asistencias del estudiante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByStatus($estado)
    {
        try {
            $asistencias = asistencias_estudiantes::with(['sesionClase', 'estudiante'])
                ->where('estado', $estado)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $asistencias
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las asistencias por estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function registerAttendance(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'sesion_clase_id' => 'required|exists:sesiones_clase,id',
                'estudiante_id' => 'required|exists:usuarios,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar si ya existe un registro de asistencia
            $existente = asistencias_estudiantes::where('sesion_clase_id', $request->sesion_clase_id)
                ->where('estudiante_id', $request->estudiante_id)
                ->first();

            if ($existente) {
                return response()->json([
                    'success' => false,
                    'message' => 'La asistencia ya fue registrada para este estudiante en esta sesión'
                ], 409);
            }

            $asistencia = asistencias_estudiantes::create([
                'sesion_clase_id' => $request->sesion_clase_id,
                'estudiante_id' => $request->estudiante_id,
                'hora_registro' => Carbon::now(),
                'estado' => 'presente',
                'validado_por_qr' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Asistencia registrada exitosamente',
                'data' => $asistencia
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la asistencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAttendanceReport($student_id, $group_id)
    {
        try {
            $asistencias = asistencias_estudiantes::with(['sesionClase'])
                ->where('estudiante_id', $student_id)
                ->whereHas('sesionClase.horario', function($query) use ($group_id) {
                    $query->where('grupo_id', $group_id);
                })
                ->get();

            $total = $asistencias->count();
            $presentes = $asistencias->where('estado', 'presente')->count();
            $tardes = $asistencias->where('estado', 'tarde')->count();
            $ausentes = $asistencias->where('estado', 'ausente')->count();
            $porcentaje = $total > 0 ? round(($presentes / $total) * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'asistencias' => $asistencias,
                    'resumen' => [
                        'total' => $total,
                        'presentes' => $presentes,
                        'tardes' => $tardes,
                        'ausentes' => $ausentes,
                        'porcentaje_asistencia' => $porcentaje
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el reporte de asistencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStudentStatistics($student_id)
    {
        try {
            $asistencias = asistencias_estudiantes::where('estudiante_id', $student_id)->get();

            $total = $asistencias->count();
            $presentes = $asistencias->where('estado', 'presente')->count();
            $tardes = $asistencias->where('estado', 'tarde')->count();
            $ausentes = $asistencias->where('estado', 'ausente')->count();
            $porcentaje = $total > 0 ? round(($presentes / $total) * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'presentes' => $presentes,
                    'tardes' => $tardes,
                    'ausentes' => $ausentes,
                    'porcentaje_asistencia' => $porcentaje
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas del estudiante',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
