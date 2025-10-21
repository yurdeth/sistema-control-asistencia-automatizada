<?php

namespace App\Http\Controllers;

use App\Models\configuracion_sistema;
use App\RolesEnum;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ConfiguracionSistemaController extends Controller {
    public function index(): JsonResponse {
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
            $configuraciones = configuracion_sistema::with(['usuarioModificacion'])->get();

            if ($configuraciones->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron configuraciones del sistema'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $configuraciones
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las configuraciones del sistema',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse {
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
            $configuracion = configuracion_sistema::with(['usuarioModificacion'])->find($id);

            if (!$configuracion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuración no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $configuracion
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la configuración',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByKey($clave): JsonResponse {
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
            $configuracion = configuracion_sistema::where('clave', $clave)->first();

            if (!$configuracion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuración no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $configuracion
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la configuración',
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
        if ($user_rolName != RolesEnum::ROOT->value) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        $request->merge([
            'clave' => $this->sanitizeInput($request->input('clave')),
            'valor' => $this->sanitizeInput($request->input('valor')),
            'tipo_dato' => $this->sanitizeInput($request->input('tipo_dato')),
            'descripcion' => $this->sanitizeInput($request->input('descripcion')),
            'categoria' => $this->sanitizeInput($request->input('categoria')),
        ]);

        $rules = [
            'clave' => 'required|string|max:100|unique:configuracion_sistema,clave',
            'valor' => 'required|string',
            'tipo_dato' => 'required|in:string,integer,boolean',
            'descripcion' => 'nullable|string',
            'categoria' => 'nullable|string|max:50',
            'modificable' => 'boolean',
        ];

        $messages = [
            'clave.required' => 'La clave es obligatoria.',
            'clave.unique' => 'Ya existe una configuración con esa clave.',
            'clave.max' => 'La clave no puede exceder 100 caracteres.',
            'valor.required' => 'El valor es obligatorio.',
            'tipo_dato.required' => 'El tipo de dato es obligatorio.',
            'tipo_dato.in' => 'El tipo de dato debe ser: string, integer o boolean.',
            'categoria.max' => 'La categoría no puede exceder 50 caracteres.',
        ];

        try {
            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $configuracion = configuracion_sistema::create([
                'clave' => $request->clave,
                'valor' => $request->valor,
                'tipo_dato' => $request->tipo_dato,
                'descripcion' => $request->descripcion,
                'categoria' => $request->categoria,
                'modificable' => $request->input('modificable', true),
                'usuario_identificacion_id' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Configuración creada exitosamente',
                'data' => $configuracion
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la configuración',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse {
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
            $configuracion = configuracion_sistema::find($id);

            if (!$configuracion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuración no encontrada'
                ], 404);
            }

            // Verificar si la configuración es modificable
            if (!$configuracion->modificable) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta configuración no es modificable'
                ], 403);
            }

            $request->merge([
                'valor' => $this->sanitizeInput($request->input('valor')),
                'descripcion' => $this->sanitizeInput($request->input('descripcion')),
            ]);

            $rules = [
                'valor' => 'required|string',
                'descripcion' => 'nullable|string',
            ];

            $messages = [
                'valor.required' => 'El valor es obligatorio.',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $configuracion->update([
                'valor' => $request->valor,
                'descripcion' => $request->descripcion ?? $configuracion->descripcion,
                'usuario_identificacion_id' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Configuración actualizada exitosamente',
                'data' => $configuracion
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la configuración',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse {
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
            $configuracion = configuracion_sistema::find($id);

            if (!$configuracion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuración no encontrada'
                ], 404);
            }

            // Verificar si la configuración es modificable
            if (!$configuracion->modificable) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta configuración no puede ser eliminada'
                ], 403);
            }

            DB::beginTransaction();

            $configuracion->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Configuración eliminada exitosamente'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la configuración',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByCategory($categoria): JsonResponse {
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
            $configuraciones = configuracion_sistema::where('categoria', $categoria)->get();

            if ($configuraciones->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "No se encontraron configuraciones en la categoría: {$categoria}"
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $configuraciones
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las configuraciones por categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getModifiable(): JsonResponse {
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
            $configuraciones = configuracion_sistema::where('modificable', true)->get();

            if ($configuraciones->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron configuraciones modificables'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $configuraciones
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las configuraciones modificables',
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
