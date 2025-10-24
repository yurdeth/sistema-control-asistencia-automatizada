<?php

namespace App\Http\Controllers;

use App\Models\materias;
use App\RolesEnum;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MateriasController extends Controller {
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $materias = Cache::remember('materias_all', 60, function () {
                return materias::limit(50)->get();
            });

            if ($materias->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron materias',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Materias encontradas',
                'success' => true,
                'data' => $materias
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las materias',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user_rolName = $this->getUserRoleName();
        $rolesPermitidos = [
            RolesEnum::ROOT->value,
            RolesEnum::ADMINISTRADOR_ACADEMICO->value,
            RolesEnum::JEFE_DEPARTAMENTO->value,
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        $request->merge([
            'codigo' => $this->sanitizeInput($request->input('codigo', '')),
            'nombre' => $this->sanitizeInput($request->input('nombre', '')),
            'descripcion' => $this->sanitizeInput($request->input('descripcion', '')),
            'carrera_id' => $this->sanitizeInput($request->input('carrera_id', '')),
            'estado' => $this->sanitizeInput($request->input('estado', 'activa')),
        ]);

        $rules = [
            'codigo' => 'required|string|max:10|unique:materias,codigo',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'carrera_id' => 'required|integer|exists:carreras,id',
            'estado' => 'required|in:activa,inactiva',
        ];

        $messages = [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.string' => 'El código debe ser una cadena de texto.',
            'codigo.max' => 'El código no debe exceder los 10 caracteres.',
            'codigo.unique' => 'El código ya está en uso.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no debe exceder los 100 caracteres.',
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción no debe exceder los 255 caracteres.',
            'carrera_id.required' => 'El ID de la carrera es obligatorio.',
            'carrera_id.integer' => 'El ID de la carrera debe ser un número entero.',
            'carrera_id.exists' => 'La carrera especificada no existe.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser "activa" o "inactiva".',
        ];

        try {
            $validation = $request->validate($rules, $messages);

            DB::beginTransaction();
            
            $materias = DB::table('materias')->insert([
                'codigo' => $request->codigo,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'carrera_id' => $request->carrera_id,
                'estado' => $request->estado                          
            ]);
            
            DB::commit();

            return response()->json([
                'message' => 'Materia creada exitosamente',
                'success' => true,
                'data' => 
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear la materia',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function sanitizeInput($input): string {
        return htmlspecialchars(strip_tags(trim($input)));
    }

    /**
     * Display the specified resource.
     */
    public function show(int $materia_id): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $materia = materias::find($materia_id);

            if (!$materia) {
                return response()->json([
                    'message' => 'Materia no encontrada',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Materia encontrada',
                'success' => true,
                'data' => $materia
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener la materia',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, int $materia_id): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user_rolName = $this->getUserRoleName();
        $rolesPermitidos = [
            RolesEnum::ROOT->value,
            RolesEnum::ADMINISTRADOR_ACADEMICO->value,
            RolesEnum::JEFE_DEPARTAMENTO->value,
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        $request->merge([
            'codigo' => $this->sanitizeInput($request->input('codigo', '')),
            'nombre' => $this->sanitizeInput($request->input('nombre', '')),
            'descripcion' => $this->sanitizeInput($request->input('descripcion', '')),
            'carrera_id' => $this->sanitizeInput($request->input('carrera_id', '')),
            'estado' => $this->sanitizeInput($request->input('estado', '')),
        ]);

        $rules = [
            'codigo' => 'sometimes|required|string|max:10|unique:materias,codigo,' . $materia_id,
            'nombre' => 'sometimes|required|string|max:100',
            'descripcion' => 'sometimes|nullable|string|max:255',
            'carrera_id' => 'sometimes|required|integer|exists:carreras,id',
            'estado' => 'sometimes|required|in:activa,inactiva',
        ];

        $messages = [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.string' => 'El código debe ser una cadena de texto.',
            'codigo.max' => 'El código no debe exceder los 10 caracteres.',
            'codigo.unique' => 'El código ya está en uso.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no debe exceder los 100 caracteres.',
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción no debe exceder los 255 caracteres.',
            'carrera_id.required' => 'El ID de la carrera es obligatorio.',
            'carrera_id.integer' => 'El ID de la carrera debe ser un número entero.',
            'carrera_id.exists' => 'La carrera especificada no existe.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser "activa" o "inactiva".',
        ];

        try {
            $validation = $request->validate($rules, $messages);

            $materia = materias::find($materia_id);
            if (!$materia) {
                return response()->json([
                    'message' => 'Materia no encontrada',
                    'success' => false
                ], 404);
            }

            DB::beginTransaction();

            if ($request->has('codigo')) {
                $materia->codigo = $validation['codigo'];
            }
            if ($request->has('nombre')) {
                $materia->nombre = $validation['nombre'];
            }
            if ($request->has('descripcion')) {
                $materia->descripcion = $validation['descripcion'];
            }
            if ($request->has('carrera_id')) {
                $materia->carrera_id = $validation['carrera_id'];
            }
            if ($request->has('estado')) {
                $materia->estado = $validation['estado'];
            }

            $materia->save();

            DB::commit();

            return response()->json([
                'message' => 'Materia actualizada exitosamente',
                'success' => true,
                'data' => $materia
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar la materia',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $materia_id): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user_rolName = $this->getUserRoleName();
        $rolesPermitidos = [
            RolesEnum::ROOT->value,
            RolesEnum::ADMINISTRADOR_ACADEMICO->value,
            RolesEnum::JEFE_DEPARTAMENTO->value,
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            DB::beginTransaction();

            $materia = materias::where('id', $materia_id)->lockForUpdate()->first();
            if (!$materia) {
                return response()->json([
                    'message' => 'Materia no encontrada',
                    'success' => false
                ], 404);
            }

            $materia->delete();
            DB::commit();

            return response()->json([
                'message' => 'Materia eliminada exitosamente',
                'success' => true
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar la materia',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMateriasByCareerId(int $career_id): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $materias = (new materias())->getSubjectsByCareerId($career_id);

            if ($materias->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron materias para el departamento especificado',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Materias encontradas',
                'success' => true,
                'data' => $materias
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las materias',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMateriasByStatus(string $estado): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user_rolName = $this->getUserRoleName();
        $rolesPermitidos = [
            RolesEnum::ROOT->value,
            RolesEnum::ADMINISTRADOR_ACADEMICO->value,
            RolesEnum::JEFE_DEPARTAMENTO->value,
            RolesEnum::COORDINADOR_CARRERAS->value,
            RolesEnum::DOCENTE
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $materias = (new materias())->getSubjectsByStatus($this->sanitizeInput($estado));

            if ($materias->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron materias con el estado especificado',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Materias encontradas',
                'success' => true,
                'data' => $materias
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las materias',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSubjectsByUserId(int $user_id): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $materias = (new materias())->getSubjectsByUserId($user_id);

            if ($materias->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron materias para el usuario especificado',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Materias encontradas',
                'success' => true,
                'data' => $materias
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las materias',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMySubjects(): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $materias = (new materias())->getMySubjects();

            if ($materias->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron materias para el usuario autenticado',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Materias encontradas',
                'success' => true,
                'data' => $materias
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las materias',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getUserRoleName(): string|null {
        return DB::table('usuario_roles')
            ->join('users', 'usuario_roles.usuario_id', '=', 'users.id')
            ->join('roles', 'usuario_roles.rol_id', '=', 'roles.id')
            ->where('users.id', Auth::id())
            ->value('roles.nombre');
    }
}
