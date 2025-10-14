<?php

namespace App\Http\Controllers;

use App\Http\Requests\Storesolicitudes_inscripcionRequest;
use App\Http\Requests\Updatesolicitudes_inscripcionRequest;
use App\Models\solicitudes_inscripcion;
use Illuminate\Http\Request;  
use Illuminate\Support\Facades\Validator; 

class SolicitudesInscripcionController extends Controller
{
     public function index()
    {
        try {
            $solicitudes = solicitudes_inscripcion::with(['estudiante', 'grupo', 'respondidoPor'])->get();
            return response()->json([
                'success' => true,
                'data' => $solicitudes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las solicitudes de inscripción',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $solicitud = solicitudes_inscripcion::with(['estudiante', 'grupo', 'respondidoPor'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $solicitud
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitud no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'estudiante_id' => 'required|exists:usuarios,id',
                'grupo_id' => 'required|exists:grupos,id',
                'tipo_solicitud' => 'required|in:estudiante_solicita,docente_invita',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $solicitud = solicitudes_inscripcion::create([
                'estudiante_id' => $request->estudiante_id,
                'grupo_id' => $request->grupo_id,
                'tipo_solicitud' => $request->tipo_solicitud,
                'estado' => 'pendiente'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud creada exitosamente',
                'data' => $solicitud
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function edit(Request $request, $id)
    {
        try {
            $solicitud = solicitudes_inscripcion::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'estudiante_id' => 'sometimes|exists:usuarios,id',
                'grupo_id' => 'sometimes|exists:grupos,id',
                'tipo_solicitud' => 'sometimes|in:estudiante_solicita,docente_invita',
                'estado' => 'sometimes|in:pendiente,aceptada,rechazada,cancelada'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $solicitud->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Solicitud actualizada exitosamente',
                'data' => $solicitud
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $solicitud = solicitudes_inscripcion::findOrFail($id);
            $solicitud->delete();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud eliminada exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByStudent($id)
    {
        try {
            $solicitudes = solicitudes_inscripcion::with(['grupo', 'respondidoPor'])
                ->where('estudiante_id', $id)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $solicitudes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las solicitudes del estudiante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByGroup($id)
    {
        try {
            $solicitudes = solicitudes_inscripcion::with(['estudiante', 'respondidoPor'])
                ->where('grupo_id', $id)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $solicitudes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las solicitudes del grupo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByStatus($estado)
    {
        try {
            $solicitudes = solicitudes_inscripcion::with(['estudiante', 'grupo', 'respondidoPor'])
                ->where('estado', $estado)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $solicitudes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las solicitudes por estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByType($tipo)
    {
        try {
            $solicitudes = solicitudes_inscripcion::with(['estudiante', 'grupo', 'respondidoPor'])
                ->where('tipo_solicitud', $tipo)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $solicitudes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las solicitudes por tipo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function acceptRequest(Request $request, $id)
    {
        try {
            $solicitud = solicitudes_inscripcion::findOrFail($id);
            
            $solicitud->update([
                'estado' => 'aceptada',
                'respondido_por_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud aceptada exitosamente',
                'data' => $solicitud
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aceptar la solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function rejectRequest(Request $request, $id)
    {
        try {
            $solicitud = solicitudes_inscripcion::findOrFail($id);
            
            $solicitud->update([
                'estado' => 'rechazada',
                'respondido_por_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud rechazada exitosamente',
                'data' => $solicitud
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar la solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPendingByProfessor($id)
    {
        try {
            $solicitudes = solicitudes_inscripcion::with(['estudiante', 'grupo'])
                ->whereHas('grupo', function($query) use ($id) {
                    $query->where('docente_id', $id);
                })
                ->where('estado', 'pendiente')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $solicitudes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las solicitudes pendientes del docente',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
