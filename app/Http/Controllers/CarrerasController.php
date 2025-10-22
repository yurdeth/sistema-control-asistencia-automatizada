<?php

namespace App\Http\Controllers;

use App\Models\carreras;
use App\RolesEnum;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CarrerasController extends Controller {
    /**
     * Obtener todas las carreras
     */
    public function index(): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $carreras = Cache::remember('carreras_all', 60, function () {
                return (new carreras())->getAllCarreras();
            });

            if ($carreras->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron carreras',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Carreras obtenidas exitosamente',
                'success' => true,
                'data' => $carreras
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las carreras',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(int $carrera_id): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $carrera = (new carreras())->getCarreraById($carrera_id);

            if (!$carrera) {
                return response()->json([
                    'message' => 'Carrera no encontrada',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Carrera obtenida exitosamente',
                'success' => true,
                'data' => $carrera
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener la carrera',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(int $carrera_id): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user_rolName = $this->getUserRoleName();

        if ($user_rolName != RolesEnum::ADMINISTRADOR_ACADEMICO->value) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $carrera = DB::table('carreras')->where('id', $carrera_id)->lockForUpdate()->first();

            if (!$carrera) {
                return response()->json([
                    'message' => 'Carrera no encontrada',
                    'success' => false
                ], 404);
            }

            DB::table('carreras')->where('id', $carrera_id)->delete();
            Cache::forget('carreras_all');
            return response()->json([
                'message' => 'Carrera eliminada exitosamente',
                'success' => true
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la carrera',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user_rolName = $this->getUserRoleName();
        $rolesPermitidos = [
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
            'nombre' => $this->sanitizeInput($request->input('nombre')),
            'departamento_id' => (int)$request->input('departamento_id'),
        ]);

        $rules = [
            'nombre' => ['required', 'string', 'max:255', 'unique:carreras,id', 'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/u'],
            'departamento_id' => 'required|integer|exists:departamentos,id',
        ];

        $messages = [
            'nombre.required' => 'El nombre de la carrera es obligatorio.',
            'nombre.unique' => 'El nombre de la carrera ya existe.',
            'nombre.string' => 'El nombre de la carrera debe ser una cadena de texto.',
            'nombre.max' => 'El nombre de la carrera no debe exceder los 255 caracteres.',
            'departamento_id.required' => 'El ID del departamento es obligatorio.',
            'departamento_id.integer' => 'El ID del departamento debe ser un número entero.',
            'departamento_id.exists' => 'El departamento especificado no existe.',
        ];

        try {
            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            DB::table('carreras')->insert([
                'nombre' => $request->nombre,
                'departamento_id' => $request->departamento_id,
                'estado' => $request->estado ?? 'activa',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            Cache::forget('carreras_all');

            return response()->json([
                'message' => 'Carrera creada exitosamente',
                'success' => true,
                'data' => (new carreras())->getAllCarreras()
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear la carrera',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, int $carrera_id) {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user_rolName = $this->getUserRoleName();
        $rolesPermitidos = [
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
            'nombre' => $this->sanitizeInput($request->input('nombre')),
            'departamento_id' => (int)$request->input('departamento_id'),
        ]);

        $rules = [
            'nombre' => ['string', 'max:255', 'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/u'],
            'departamento_id' => 'integer|exists:departamentos,id',
        ];

        $messages = [
            'nombre.string' => 'El nombre de la carrera debe ser una cadena de texto.',
            'nombre.max' => 'El nombre de la carrera no debe exceder los 255 caracteres.',
            'departamento_id.integer' => 'El ID del departamento debe ser un número entero.',
            'departamento_id.exists' => 'El departamento especificado no existe.',
        ];

        try {
            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $carrera = DB::table('carreras')->where('id', $carrera_id)->lockForUpdate()->first();
            if (!$carrera) {
                return response()->json([
                    'message' => 'Carrera no encontrada',
                    'success' => false
                ], 404);
            }

            if ($request->has('nombre')) {
                DB::table('carreras')->where('id', $carrera_id)->update([
                    'nombre' => $request->input('nombre'),
                    'updated_at' => now(),
                ]);
            }

            if ($request->has('departamento_id')) {
                DB::table('carreras')->where('id', $carrera_id)->update([
                    'departamento_id' => $request->input('departamento_id'),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            Cache::forget('carreras_all');

            return response()->json([
                'message' => 'Carrera actualizada exitosamente',
                'success' => true,
                'data' => (new carreras())->getAllCarreras()
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar la carrera',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener carreras por departamento
     */
    public function getByDepartamento($departamentoId): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $carreras = DB::table('carreras')
                ->select('id', 'nombre', 'departamento_id')
                ->where('departamento_id', $departamentoId)
                ->orderBy('nombre', 'asc')
                ->get();

            return response()->json([
                'message' => 'Carreras obtenidas exitosamente',
                'success' => true,
                'data' => $carreras
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las carreras',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCareersByStatus(Request $request, string $estado): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $estado = $this->sanitizeInput($estado);

         if (!in_array($estado, ['activa', 'inactiva'])) {
            return response()->json([
                'message' => 'Estado inválido. Los valores permitidos son "activa" o "inactiva".',
                'success' => false
            ], 400);
        }

        try {
            $carreras = (new carreras())->getByStatus($estado);

            if ($carreras->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron carreras con el estado especificado',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Carreras obtenidas exitosamente',
                'success' => true,
                'data' => $carreras
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las carreras',
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

    private function sanitizeInput($input): string {
        return htmlspecialchars(strip_tags(trim($input)));
    }
}
