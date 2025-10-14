<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoremantenimientosRequest;
use App\Http\Requests\UpdatemantenimientosRequest;
use App\Models\mantenimientos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; 
use Carbon\Carbon;  


class MantenimientosController extends Controller
{
    public function index()
    {
        try {
            $mantenimientos = mantenimientos::with(['aula', 'usuarioRegistro'])->get();
            return response()->json([
                'success' => true,
                'data' => $mantenimientos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los mantenimientos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $mantenimiento = mantenimientos::with(['aula', 'usuarioRegistro'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $mantenimiento
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Mantenimiento no encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'aula_id' => 'required|exists:aulas,id',
                'motivo' => 'required|string',
                'fecha_inicio' => 'required|date',
                'fecha_fin_programada' => 'required|date|after:fecha_inicio',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $mantenimiento = mantenimientos::create([
                'aula_id' => $request->aula_id,
                'usuario_registro_id' => auth()->id(),
                'motivo' => $request->motivo,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin_programada' => $request->fecha_fin_programada,
                'estado' => 'programado'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento creado exitosamente',
                'data' => $mantenimiento
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el mantenimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function edit(Request $request, $id)
    {
        try {
            $mantenimiento = mantenimientos::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'aula_id' => 'sometimes|exists:aulas,id',
                'motivo' => 'sometimes|string',
                'fecha_inicio' => 'sometimes|date',
                'fecha_fin_programada' => 'sometimes|date',
                'fecha_fin_real' => 'sometimes|date',
                'estado' => 'sometimes|in:programado,en_proceso,finalizado,cancelado'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $mantenimiento->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento actualizado exitosamente',
                'data' => $mantenimiento
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el mantenimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $mantenimiento = mantenimientos::findOrFail($id);
            $mantenimiento->delete();

            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento eliminado exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el mantenimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByClassroom($id)
    {
        try {
            $mantenimientos = mantenimientos::with(['usuarioRegistro'])
                ->where('aula_id', $id)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $mantenimientos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los mantenimientos del aula',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByStatus($estado)
    {
        try {
            $mantenimientos = mantenimientos::with(['aula', 'usuarioRegistro'])
                ->where('estado', $estado)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $mantenimientos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los mantenimientos por estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUpcoming()
    {
        try {
            $mantenimientos = mantenimientos::with(['aula', 'usuarioRegistro'])
                ->where('estado', 'programado')
                ->where('fecha_inicio', '>=', Carbon::now())
                ->orderBy('fecha_inicio', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $mantenimientos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los próximos mantenimientos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function finishMaintenance(Request $request, $id)
    {
        try {
            $mantenimiento = mantenimientos::findOrFail($id);
            
            $mantenimiento->update([
                'fecha_fin_real' => Carbon::now(),
                'estado' => 'finalizado'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento finalizado exitosamente',
                'data' => $mantenimiento
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al finalizar el mantenimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function changeStatus(Request $request, $id)
    {
        try {
            $mantenimiento = mantenimientos::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'estado' => 'required|in:programado,en_proceso,finalizado,cancelado'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $mantenimiento->update(['estado' => $request->estado]);

            return response()->json([
                'success' => true,
                'message' => 'Estado del mantenimiento actualizado exitosamente',
                'data' => $mantenimiento
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado del mantenimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByDateRange(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after:fecha_inicio',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $mantenimientos = mantenimientos::with(['aula', 'usuarioRegistro'])
                ->whereBetween('fecha_inicio', [$request->fecha_inicio, $request->fecha_fin])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $mantenimientos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los mantenimientos por rango de fecha',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
