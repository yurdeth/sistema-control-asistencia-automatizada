<?php

namespace App\Http\Controllers;

use App\Models\grupos;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GruposController extends Controller {

    public function index(): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $grupos = grupos::with(['materia', 'ciclo', 'docente'])->get();
        if ($grupos->isEmpty()) {
            return response()->json([
                'message' => 'No hay grupos disponibles',
                'success' => true
            ], 404);
        }

        return response()->json([
            'message' => 'Grupos obtenidos exitosamente',
            'success' => true,
            'data' => $grupos
        ], 200);
    }

    public function show($id): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $grupo = grupos::with(['materia', 'ciclo', 'docente', 'horarios'])->find($id);

        if (!$grupo) {
            return response()->json([
                'message' => 'Grupo no encontrado',
                'success' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Grupo obtenido exitosamente',
            'success' => true,
            'data' => $grupo
        ], 200);
    }

    public function store(Request $request): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check() || $user_rol == 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $request->merge([
            'materia_id' => $this->sanitizeInput($request->materia_id),
            'ciclo_id' => $this->sanitizeInput($request->ciclo_id),
            'docente_id' => $this->sanitizeInput($request->docente_id),
            'numero_grupo' => $this->sanitizeInput($request->numero_grupo),
            'capacidad_maxima' => $this->sanitizeInput($request->capacidad_maxima),
            'estado' => $this->sanitizeInput($request->estado)
        ]);

        $rules = [
            'materia_id' => 'required|exists:materias,id',
            'ciclo_id' => 'required|exists:ciclos_academicos,id',
            'docente_id' => 'required|exists:usuarios,id',
            'numero_grupo' => 'required|string|max:10',
            'capacidad_maxima' => 'required|integer|min:1',
            'estado' => 'required|in:activo,finalizado,cancelado'
        ];

        $messages = [
            'materia_id.required' => 'El campo materia_id es obligatorio.',
            'materia_id.exists' => 'La materia especificada no existe.',
            'ciclo_id.required' => 'El campo ciclo_id es obligatorio.',
            'ciclo_id.exists' => 'El ciclo académico especificado no existe.',
            'docente_id.required' => 'El campo docente_id es obligatorio.',
            'docente_id.exists' => 'El docente especificado no existe.',
            'numero_grupo.required' => 'El campo numero_grupo es obligatorio.',
            'numero_grupo.string' => 'El campo numero_grupo debe ser una cadena de texto.',
            'numero_grupo.max' => 'El campo numero_grupo no debe exceder los 10 caracteres.',
            'capacidad_maxima.required' => 'El campo capacidad_maxima es obligatorio.',
            'capacidad_maxima.integer' => 'El campo capacidad_maxima debe ser un número entero.',
            'capacidad_maxima.min' => 'El campo capacidad_maxima debe ser al menos 1.',
            'estado.required' => 'El campo estado es obligatorio.',
            'estado.in' => 'El campo estado debe ser uno de los siguientes valores: activo, finalizado, cancelado.'
        ];

        try {
            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                    'success' => false
                ], 422);
            }

            DB::beginTransaction();

            $validatedData = $validator->validated();

            $grupo = grupos::create($validatedData);

            DB::commit();

            return response()->json([
                'message' => 'Grupo creado exitosamente',
                'success' => true,
                'data' => $grupo
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear el grupo',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function edit(Request $request, $id): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check() || $user_rol == 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $grupo = grupos::find($id);

        if (!$grupo) {
            return response()->json([
                'message' => 'Grupo no encontrado',
                'success' => false
            ], 404);
        }

        $request->merge([
            'materia_id' => $this->sanitizeInput($request->materia_id),
            'ciclo_id' => $this->sanitizeInput($request->ciclo_id),
            'docente_id' => $this->sanitizeInput($request->docente_id),
            'numero_grupo' => $this->sanitizeInput($request->numero_grupo),
            'capacidad_maxima' => $this->sanitizeInput($request->capacidad_maxima),
            'estado' => $this->sanitizeInput($request->estado)
        ]);

        $rules = [
            'materia_id' => 'required|exists:materias,id',
            'ciclo_id' => 'required|exists:ciclos_academicos,id',
            'docente_id' => 'required|exists:usuarios,id',
            'numero_grupo' => 'required|string|max:10',
            'capacidad_maxima' => 'required|integer|min:1',
            'estado' => 'required|in:activo,finalizado,cancelado'
        ];

        $messages = [
            'materia_id.required' => 'El campo materia_id es obligatorio.',
            'materia_id.exists' => 'La materia especificada no existe.',
            'ciclo_id.required' => 'El campo ciclo_id es obligatorio.',
            'ciclo_id.exists' => 'El ciclo académico especificado no existe.',
            'docente_id.required' => 'El campo docente_id es obligatorio.',
            'docente_id.exists' => 'El docente especificado no existe.',
            'numero_grupo.required' => 'El campo numero_grupo es obligatorio.',
            'numero_grupo.string' => 'El campo numero_grupo debe ser una cadena de texto.',
            'numero_grupo.max' => 'El campo numero_grupo no debe exceder los 10 caracteres.',
            'capacidad_maxima.required' => 'El campo capacidad_maxima es obligatorio.',
            'capacidad_maxima.integer' => 'El campo capacidad_maxima debe ser un número entero.',
            'capacidad_maxima.min' => 'El campo capacidad_maxima debe ser al menos 1.',
            'estado.required' => 'El campo estado es obligatorio.',
            'estado.in' => 'El campo estado debe ser uno de los siguientes valores: activo, finalizado, cancelado.'
        ];

        try {
            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                    'success' => false
                ], 422);
            }

            DB::beginTransaction();

            $validatedData = $validator->validated();

            $grupo->update($validatedData);

            DB::commit();

            return response()->json([
                'message' => 'Grupo actualizado exitosamente',
                'success' => true,
                'data' => $grupo
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar el grupo',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function destroy($id): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check() || $user_rol == 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $grupo = grupos::where('id', $id)->lockForUpdate()->first();

            if (!$grupo) {
                return response()->json([
                    'message' => 'Grupo no encontrado',
                    'success' => false
                ], 404);
            }

            $grupo->delete();

            return response()->json([
                'message' => 'Grupo eliminado exitosamente',
                'success' => true
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el grupo',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function getGroupsBySubject($id): JsonResponse {
        $grupos = grupos::with(['ciclo', 'docente'])
            ->where('materia_id', $id)
            ->get();

        return response()->json([
            'message' => 'Grupos obtenidos exitosamente',
            'success' => true,
            'data' => $grupos
        ], 200);
    }

    public function getGroupsByCycle($id): JsonResponse {
        $grupos = grupos::with(['materia', 'docente'])
            ->where('ciclo_id', $id)
            ->get();

        return response()->json($grupos, 200);
    }

    public function getGroupsByProfessor($id): JsonResponse {
        $grupos = grupos::with(['materia', 'ciclo'])
            ->where('docente_id', $id)
            ->get();

        return response()->json($grupos, 200);
    }

    public function getGroupsByStatus($estado): JsonResponse {
        $grupos = grupos::with(['materia', 'ciclo', 'docente'])
            ->where('estado', $estado)
            ->get();

        return response()->json($grupos, 200);
    }

    public function getAvailableGroups(): JsonResponse {
        $grupos = grupos::with(['materia', 'ciclo', 'docente'])
            ->whereColumn('estudiantes_inscritos', '<', 'capacidad_maxima')
            ->where('estado', 'activo')
            ->get();

        return response()->json($grupos, 200);
    }

    public function getGroupsByNumber(Request $request, $numero_grupo): JsonResponse {
        $query = grupos::with(['materia', 'ciclo', 'docente'])
            ->where('numero_grupo', $numero_grupo);

        if ($request->has('materia_id')) {
            $query->where('materia_id', $request->materia_id);
        }

        if ($request->has('ciclo_id')) {
            $query->where('ciclo_id', $request->ciclo_id);
        }

        $grupos = $query->get();

        return response()->json($grupos, 200);
    }

    private function getUserRole() {
        return DB::table('usuario_roles')
            ->join('users', 'usuario_roles.usuario_id', '=', 'users.id')
            ->where('users.id', Auth::id())
            ->value('usuario_roles.rol_id');
    }

    private function sanitizeInput($input): string {
        return htmlspecialchars(strip_tags(trim($input)));
    }
}
