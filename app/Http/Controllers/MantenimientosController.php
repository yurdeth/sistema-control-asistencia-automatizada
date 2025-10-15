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
            
            if ($mantenimientos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron mantenimientos'
                ], 404);
            }

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
            $mantenimiento = mantenimientos::with(['aula', 'usuarioRegistro'])->find($id);
            
            if (!$mantenimiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mantenimiento no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $mantenimiento
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el mantenimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'aula_id' => 'required|exists:aulas,id',
                'usuario_registro_id' => 'required|exists:users,id',
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

            // Validar si hay un mantenimiento activo para la misma aula en las mismas fechas
            $conflicto = mantenimientos::where('aula_id', $request->aula_id)
                ->whereIn('estado', ['programado', 'en_proceso'])
                ->where(function($query) use ($request) {
                    $query->whereBetween('fecha_inicio', [$request->fecha_inicio, $request->fecha_fin_programada])
                        ->orWhereBetween('fecha_fin_programada', [$request->fecha_inicio, $request->fecha_fin_programada]);
                })
                ->first();

            if ($conflicto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un mantenimiento programado para esta aula en el rango de fechas seleccionado'
                ], 409);
            }

            $mantenimiento = mantenimientos::create([
                'aula_id' => $request->aula_id,
                'usuario_registro_id' => $request->usuario_registro_id,
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
        $mantenimiento = mantenimientos::find($id);

        if (!$mantenimiento) {
            return response()->json([
                'success' => false,
                'message' => 'Mantenimiento no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'aula_id' => 'sometimes|exists:aulas,id',
            'motivo' => 'sometimes|string|max:500',
            'fecha_inicio' => 'sometimes|date',
            'fecha_fin_programada' => 'sometimes|date|after_or_equal:fecha_inicio',
            'fecha_fin_real' => 'sometimes|date|after_or_equal:fecha_inicio',
            'estado' => 'sometimes|in:programado,en_proceso,finalizado,cancelado'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $validator->errors()
            ], 422);
        }

       
        $aula_id = $request->aula_id ?? $mantenimiento->aula_id;
        $fecha_inicio = $request->fecha_inicio ?? $mantenimiento->fecha_inicio;
        $fecha_fin = $request->fecha_fin_programada ?? $mantenimiento->fecha_fin_programada;

        $conflicto = mantenimientos::where('aula_id', $aula_id)
            ->where('id', '!=', $id) 
            ->where('estado', '!=', 'cancelado')
            ->where(function($query) use ($fecha_inicio, $fecha_fin) {
                $query->whereBetween('fecha_inicio', [$fecha_inicio, $fecha_fin])
                    ->orWhereBetween('fecha_fin_programada', [$fecha_inicio, $fecha_fin])
                    ->orWhere(function($q) use ($fecha_inicio, $fecha_fin) {
                        $q->where('fecha_inicio', '<=', $fecha_inicio)
                          ->where('fecha_fin_programada', '>=', $fecha_fin);
                    });
            })
            ->exists();

        if ($conflicto) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un mantenimiento programado para esta aula en ese período'
            ], 422);
        }

        $mantenimiento->update($request->only([
            'aula_id',
            'motivo',
            'fecha_inicio',
            'fecha_fin_programada',
            'fecha_fin_real',
            'estado'
        ]));

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
            $mantenimiento = mantenimientos::find($id);

            if (!$mantenimiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mantenimiento no encontrado'
                ], 404);
            }

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

            if ($mantenimientos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron mantenimientos para esta aula'
                ], 404);
            }

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

            if ($mantenimientos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "No se encontraron mantenimientos con estado: {$estado}"
                ], 404);
            }

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
                ->where('fecha_inicio', '>=', Carbon::today())
                ->orderBy('fecha_inicio', 'asc')
                ->get();

            if ($mantenimientos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay mantenimientos próximos programados'
                ], 404);
            }

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
            $mantenimiento = mantenimientos::find($id);

            if (!$mantenimiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mantenimiento no encontrado'
                ], 404);
            }

            // Validar que el mantenimiento esté en proceso
            if ($mantenimiento->estado !== 'en_proceso') {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede finalizar un mantenimiento con estado: {$mantenimiento->estado}. Solo se pueden finalizar mantenimientos en proceso."
                ], 400);
            }
            
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
            $mantenimiento = mantenimientos::find($id);

            if (!$mantenimiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mantenimiento no encontrado'
                ], 404);
            }

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

            if ($mantenimientos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron mantenimientos en el rango de fechas especificado'
                ], 404);
            }

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