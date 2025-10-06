<?php

namespace App\Http\Controllers;

use App\Models\materias;
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
                return materias::all();
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
        $user_rol = $this->getUserRole();
        if (!Auth::check() || $user_rol > 3) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $request->merge([
            'codigo' => $this->sanitizeInput($request->input('codigo', '')),
            'nombre' => $this->sanitizeInput($request->input('nombre', '')),
            'descripcion' => $this->sanitizeInput($request->input('descripcion', '')),
            'departamento_id' => $this->sanitizeInput($request->input('departamento_id', '')),
            'estado' => $this->sanitizeInput($request->input('estado', 'activa')),
        ]);

        $rules = [
            'codigo' => 'required|string|max:10|unique:materias,codigo',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'departamento_id' => 'required|integer|exists:departamentos,id',
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
            'departamento_id.required' => 'El ID del departamento es obligatorio.',
            'departamento_id.integer' => 'El ID del departamento debe ser un número entero.',
            'departamento_id.exists' => 'El departamento especificado no existe.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser "activa" o "inactiva".',
        ];

        try {
            $validation = $request->validate($rules, $messages);

            DB::beginTransaction();
            $materia = materias::create($validation);
            DB::commit();

            return response()->json([
                'message' => 'Materia creada exitosamente',
                'success' => true,
                'data' => $materia
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

    private function getUserRole() {
        return DB::table('usuario_roles')
            ->join('users', 'usuario_roles.usuario_id', '=', 'users.id')
            ->where('users.id', Auth::id())
            ->value('usuario_roles.rol_id');
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
        $user_rol = $this->getUserRole();
        if (!Auth::check() || $user_rol > 3) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $request->merge([
            'codigo' => $this->sanitizeInput($request->input('codigo', '')),
            'nombre' => $this->sanitizeInput($request->input('nombre', '')),
            'descripcion' => $this->sanitizeInput($request->input('descripcion', '')),
            'departamento_id' => $this->sanitizeInput($request->input('departamento_id', '')),
            'estado' => $this->sanitizeInput($request->input('estado', '')),
        ]);

        $rules = [
            'codigo' => 'sometimes|required|string|max:10|unique:materias,codigo,' . $materia_id,
            'nombre' => 'sometimes|required|string|max:100',
            'descripcion' => 'sometimes|nullable|string|max:255',
            'departamento_id' => 'sometimes|required|integer|exists:departamentos,id',
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
            'departamento_id.required' => 'El ID del departamento es obligatorio.',
            'departamento_id.integer' => 'El ID del departamento debe ser un número entero.',
            'departamento_id.exists' => 'El departamento especificado no existe.',
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
            if ($request->has('departamento_id')) {
                $materia->departamento_id = $validation['departamento_id'];
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
        $user_rol = $this->getUserRole();

        if (!Auth::check() || $user_rol > 3) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
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

    public function getMateriasByDepartment(int $department_id): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $materias = (new materias())->getSubjectsByDepartment($department_id);

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

    public function getMateriasByUserId(int $user_id): JsonResponse {
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
}
