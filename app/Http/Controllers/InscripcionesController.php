<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreinscripcionesRequest;
use App\Http\Requests\UpdateinscripcionesRequest;
use App\Models\inscripciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; 

class InscripcionesController extends Controller
{
    public function index()
    {
        try {
            $inscripciones = inscripciones::with(['estudiante', 'grupo'])->get();
            return response()->json([
                'success' => true,
                'data' => $inscripciones
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las inscripciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $inscripcion = inscripciones::with(['estudiante', 'grupo'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $inscripcion
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Inscripción no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'estudiante_id' => 'required|exists:users,id',
                'grupo_id' => 'required|exists:grupos,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar que no exista una inscripción activa
            $existente = inscripciones::where('estudiante_id', $request->estudiante_id)
                ->where('grupo_id', $request->grupo_id)
                ->where('estado', 'activo')
                ->first();

            if ($existente) {
                return response()->json([
                    'success' => false,
                    'message' => 'El estudiante ya está inscrito en este grupo'
                ], 409);
            }

            $inscripcion = inscripciones::create([
                'estudiante_id' => $request->estudiante_id,
                'grupo_id' => $request->grupo_id,
                'estado' => 'activo'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inscripción creada exitosamente',
                'data' => $inscripcion
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la inscripción',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function edit(Request $request, $id)
    {
        try {
            $inscripcion = inscripciones::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'estudiante_id' => 'sometimes|exists:usuarios,id',
                'grupo_id' => 'sometimes|exists:grupos,id',
                'estado' => 'sometimes|in:activo,retirado,finalizado'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $inscripcion->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Inscripción actualizada exitosamente',
                'data' => $inscripcion
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la inscripción',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $inscripcion = inscripciones::findOrFail($id);
            $inscripcion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Inscripción eliminada exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la inscripción',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByStudent($id)
    {
        try {
            $inscripciones = inscripciones::with(['grupo.materia', 'grupo.docente'])
                ->where('estudiante_id', $id)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $inscripciones
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las inscripciones del estudiante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByGroup($id)
    {
        try {
            $inscripciones = inscripciones::with(['estudiante'])
                ->where('grupo_id', $id)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $inscripciones
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las inscripciones del grupo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByStatus($estado)
    {
        try {
            $inscripciones = inscripciones::with(['estudiante', 'grupo'])
                ->where('estado', $estado)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $inscripciones
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las inscripciones por estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function withdrawEnrollment(Request $request, $id)
    {
        try {
            $inscripcion = inscripciones::findOrFail($id);
            
            $inscripcion->update([
                'estado' => 'retirado'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inscripción retirada exitosamente',
                'data' => $inscripcion
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al retirar la inscripción',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getActiveByStudent($student_id)
    {
        try {
            $inscripciones = inscripciones::with(['grupo.materia', 'grupo.docente', 'grupo.ciclo'])
                ->where('estudiante_id', $student_id)
                ->where('estado', 'activo')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $inscripciones
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las inscripciones activas del estudiante',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
