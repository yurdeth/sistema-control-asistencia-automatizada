<?php

namespace App\Http\Controllers;

use App\Models\departamentos;
use App\RolesEnum;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DepartamentosController extends Controller {
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
            $departamentos = Cache::remember('departamentos_all', 60, function () {
                return departamentos::all();
            });

            if ($departamentos->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron departamentos',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $departamentos,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al obtener los departamentos',
                'error' => $e->getMessage(),
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

        if ($user_rolName != RolesEnum::JEFE_DEPARTAMENTO->value) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        $request->merge([
            'nombre' => $this->sanitizeInput($request->input('nombre', '')),
            'descripcion' => $this->sanitizeInput($request->input('descripcion', '')),
        ]);

        $rules = [
            'nombre' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZñÑáÁéÉíÍóÓúÚ\s]+$/', 'unique:departamentos,nombre'],
            'descripcion' => 'required|string|max:500',
            'estado' => 'in:activo,inactivo',
        ];

        $messages = [
            'nombre.required' => 'El nombre del departamento es obligatorio.',
            'nombre.string' => 'El nombre del departamento debe ser una cadena de texto.',
            'nombre.max' => 'El nombre del departamento no debe exceder los 255 caracteres.',
            'nombre.unique' => 'El nombre del departamento ya está en uso.',
            'descripcion.required' => 'La descripción del departamento es obligatoria.',
            'descripcion.string' => 'La descripción del departamento debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción del departamento no debe exceder los 500 caracteres.',
            'estado.in' => 'El estado del departamento debe ser "activo" o "inactivo".',
        ];

        try {
            $validation = Validator::make($request->all(), $rules, $messages);

            if ($validation->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validation->errors(),
                    'success' => false
                ], 422);
            }

            DB::beginTransaction();

            $validatedData = $validation->validated();

            $departamento = departamentos::create([
                'nombre' => $validatedData['nombre'],
                'descripcion' => $validatedData['descripcion'],
                'estado' => $validatedData['estado'] ?? 'activo',
            ]);

            DB::commit();
            Cache::forget('departamentos_all');

            return response()->json([
                'message' => 'Departamento creado exitosamente',
                'success' => true,
                'data' => $departamento
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear el departamento',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $departamento_id): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $departamento = departamentos::find($departamento_id);

            if (!$departamento) {
                return response()->json([
                    'message' => 'Departamento no encontrado',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $departamento,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al obtener el departamento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, int $departament_id): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user_rolName = $this->getUserRoleName();

        if ($user_rolName != RolesEnum::JEFE_DEPARTAMENTO->value) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        $dataToMerge = [];

        if ($request->has('nombre')) {
            $dataToMerge['nombre'] = $this->sanitizeInput($request->input('nombre', ''));
        }

        if ($request->has('descripcion')) {
            $dataToMerge['descripcion'] = $this->sanitizeInput($request->input('descripcion', ''));
        }

        $request->merge($dataToMerge);

        $rules = [
            'nombre' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZñÑáÁéÉíÍóÓúÚ\s]+$/', 'unique:departamentos,nombre' . ($departament_id ? ",$departament_id" : '')],
            'descripcion' => 'sometimes|required|string|max:500',
            'estado' => 'sometimes|in:activo,inactivo',
        ];

        $messages = [
            'nombre.required' => 'El nombre del departamento es obligatorio.',
            'nombre.string' => 'El nombre del departamento debe ser una cadena de texto.',
            'nombre.max' => 'El nombre del departamento no debe exceder los 255 caracteres.',
            'nombre.unique' => 'El nombre del departamento ya está en uso.',
            'descripcion.required' => 'La descripción del departamento es obligatoria.',
            'descripcion.string' => 'La descripción del departamento debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción del departamento no debe exceder los 500 caracteres.',
            'estado.in' => 'El estado del departamento debe ser "activo" o "inactivo".',
        ];

        try {
            $validation = Validator::make($request->all(), $rules, $messages);

            if ($validation->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validation->errors(),
                    'success' => false
                ], 422);
            }

            DB::beginTransaction();

            $departamento = departamentos::where('id', $departament_id)->lockForUpdate()->first();

            if (!$departamento) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Departamento no encontrado',
                    'success' => false
                ], 404);
            }

            $validatedData = $validation->validated();

            if (isset($validatedData['nombre'])) {
                $departamento->nombre = $validatedData['nombre'];
            }

            if (isset($validatedData['descripcion'])) {
                $departamento->descripcion = $validatedData['descripcion'];
            }

            if (isset($validatedData['estado'])) {
                $departamento->estado = $validatedData['estado'];
            }

            $departamento->save();
            DB::commit();
            Cache::forget('departamentos_all');

            return response()->json([
                'message' => 'Departamento actualizado exitosamente',
                'success' => true,
                'data' => $departamento
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar el departamento',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $departament_id): JsonResponse {
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
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            DB::beginTransaction();
            $departamento = departamentos::where('id', $departament_id)->lockForUpdate()->first();

            if (!$departamento) {
                return response()->json([
                    'message' => 'Departamento no encontrado',
                    'success' => false
                ], 404);
            }

            $departamento->delete();
            DB::commit();

            return response()->json([
                'message' => 'Departamento eliminado exitosamente',
                'success' => true
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar el departamento',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function getByDepartmentName(string $nombre): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user_rolName = $this->getUserRoleName();

        if ($user_rolName != RolesEnum::JEFE_DEPARTAMENTO->value) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $departamento = departamentos::where('nombre', 'LIKE', '%' . $this->sanitizeInput($nombre) . '%')->first();

            if (!$departamento) {
                return response()->json([
                    'message' => 'Departamento no encontrado',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $departamento,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al obtener el departamento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getByStatus(string $estado): JsonResponse {
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
            RolesEnum::DOCENTE->value,
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $estado = $this->sanitizeInput($estado);
            if (!in_array($estado, ['activo', 'inactivo'])) {
                return response()->json([
                    'message' => 'Estado inválido. Debe ser "activo" o "inactivo".',
                ], 400);
            }

            $departamentos = departamentos::where('estado', $estado)->get();

            if ($departamentos->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron departamentos con el estado especificado',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $departamentos,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al obtener los departamentos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getManagers(): JsonResponse {
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
            RolesEnum::DOCENTE->value,
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $managers = DB::table('users')
                ->join('usuario_roles', 'users.id', '=', 'usuario_roles.usuario_id')
                ->join('departamentos', 'users.departamento_id', '=', 'departamentos.id')
                ->where('usuario_roles.rol_id', 3)
                ->select('users.id', 'users.nombre_completo', 'users.email', 'users.estado as estado_usuario', 'departamentos.nombre as departamento', 'departamentos.estado as estado_departamento')
                ->get();

            if ($managers->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron jefes de departamento',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $managers,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al obtener los jefes de departamento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getByManager(int $manager_id): JsonResponse {
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
            RolesEnum::DOCENTE->value,
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $departamentos = DB::table('departamentos')
                ->join('users', 'departamentos.id', '=', 'users.departamento_id')
                ->where('users.id', $manager_id)
                ->select('departamentos.id', 'departamentos.nombre', 'departamentos.descripcion', 'departamentos.estado', 'users.nombre_completo as gerente')
                ->get();

            if ($departamentos->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron departamentos para el usuario especificado',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $departamentos,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al obtener los departamentos',
                'error' => $e->getMessage(),
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

    private function sanitizeInput($input): string {
        return htmlspecialchars(strip_tags(trim($input)));
    }
}
