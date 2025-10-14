<?php

namespace App\Http\Controllers;

use App\Http\Requests\Storesesiones_claseRequest;
use App\Http\Requests\Updatesesiones_claseRequest;
use App\Models\sesiones_clase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; 

class SesionesClaseController extends Controller
{
     public function index()
    {
        try {
            $sesiones = sesiones_clase::with(['horario.grupo', 'horario.aula'])->get();
            return response()->json([
                'success' => true,
                'data' => $sesiones
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las sesiones de clase',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $sesion = sesiones_clase::with(['horario.grupo', 'horario.aula'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $sesion
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'horario_id' => 'required|exists:horarios,id',
                'fecha_clase' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $sesion = sesiones_clase::create([
                'horario_id' => $request->horario_id,
                'fecha_clase' => $request->fecha_clase,
                'estado' => 'programada'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sesión creada exitosamente',
                'data' => $sesion
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function edit(Request $request, $id)
    {
        try {
            $sesion = sesiones_clase::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'horario_id' => 'sometimes|exists:horarios,id',
                'fecha_clase' => 'sometimes|date',
                'hora_inicio_real' => 'sometimes|date_format:Y-m-d H:i:s',
                'hora_fin_real' => 'sometimes|date_format:Y-m-d H:i:s',
                'estado' => 'sometimes|in:programada,en_curso,finalizada,cancelada,sin_marcar_salida'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $sesion->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Sesión actualizada exitosamente',
                'data' => $sesion
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $sesion = sesiones_clase::findOrFail($id);
            $sesion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sesión eliminada exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByGroup($id)
    {
        try {
            $sesiones = sesiones_clase::with(['horario.aula'])
                ->whereHas('horario', function($query) use ($id) {
                    $query->where('grupo_id', $id);
                })
                ->get();

            return response()->json([
                'success' => true,
                'data' => $sesiones
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las sesiones del grupo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getBySchedule($id)
    {
        try {
            $sesiones = sesiones_clase::where('horario_id', $id)->get();

            return response()->json([
                'success' => true,
                'data' => $sesiones
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las sesiones del horario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByStatus($estado)
    {
        try {
            $sesiones = sesiones_clase::with(['horario.grupo', 'horario.aula'])
                ->where('estado', $estado)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $sesiones
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las sesiones por estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function startSession(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'horario_id' => 'required|exists:horarios,id',
                'fecha_clase' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $sesion = sesiones_clase::create([
                'horario_id' => $request->horario_id,
                'fecha_clase' => $request->fecha_clase,
                'hora_inicio_real' => Carbon::now(),
                'estado' => 'en_curso'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sesión iniciada exitosamente',
                'data' => $sesion
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar la sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function finishSession(Request $request, $id)
    {
        try {
            $sesion = sesiones_clase::findOrFail($id);
            
            $sesion->update([
                'hora_fin_real' => Carbon::now(),
                'estado' => 'finalizada'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sesión finalizada exitosamente',
                'data' => $sesion
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al finalizar la sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getTodayByProfessor($id)
    {
        try {
            $today = Carbon::today();
            $sesiones = sesiones_clase::with(['horario.grupo', 'horario.aula'])
                ->whereHas('horario.grupo', function($query) use ($id) {
                    $query->where('docente_id', $id);
                })
                ->whereDate('fecha_clase', $today)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $sesiones
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las sesiones de hoy del docente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByDate($fecha)
    {
        try {
            $sesiones = sesiones_clase::with(['horario.grupo', 'horario.aula'])
                ->whereDate('fecha_clase', $fecha)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $sesiones
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las sesiones por fecha',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function changeStatus(Request $request, $id)
    {
        try {
            $sesion = sesiones_clase::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'estado' => 'required|in:programada,en_curso,finalizada,cancelada,sin_marcar_salida'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $sesion->update(['estado' => $request->estado]);

            return response()->json([
                'success' => true,
                'message' => 'Estado de la sesión actualizado exitosamente',
                'data' => $sesion
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado de la sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
