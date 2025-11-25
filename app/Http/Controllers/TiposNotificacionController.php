<?php

namespace App\Http\Controllers;

use App\Models\tipos_notificacion;
use App\RolesEnum;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TiposNotificacionController extends Controller {
    public function index(): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $tipos = tipos_notificacion::all();

            if ($tipos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron tipos de notificación'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $tipos
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los tipos de notificación',
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

        try {
            $tipo = tipos_notificacion::find($id);

            if (!$tipo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de notificación no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $tipo
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el tipo de notificación',
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
            RolesEnum::ROOT->value,
            RolesEnum::ADMINISTRADOR_ACADEMICO->value,
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        $request->merge([
            'nombre' => $this->sanitizeInput($request->input('nombre')),
            'prioridad' => $this->sanitizeInput($request->input('prioridad')),
        ]);

        $rules = [
            'nombre' => 'required|string|unique:tipos_notificacion,nombre|max:255',
            'prioridad' => 'required|in:baja,media,alta,urgente',
        ];

        $messages = [
            'nombre.required' => 'El nombre del tipo de notificación es obligatorio.',
            'nombre.unique' => 'Ya existe un tipo de notificación con ese nombre.',
            'nombre.max' => 'El nombre no puede exceder 255 caracteres.',
            'prioridad.required' => 'La prioridad es obligatoria.',
            'prioridad.in' => 'La prioridad debe ser: baja, media, alta o urgente.',
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

            $tipo = tipos_notificacion::create([
                'nombre' => $request->nombre,
                'prioridad' => $request->prioridad,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tipo de notificación creado exitosamente',
                'data' => $tipo
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el tipo de notificación',
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

        $request->merge([
            'nombre' => $this->sanitizeInput($request->input('nombre')),
            'prioridad' => $this->sanitizeInput($request->input('prioridad')),
        ]);

        $rules = [
            'nombre' => 'required|string|max:255|unique:tipos_notificacion,nombre,' . $id,
            'prioridad' => 'required|in:baja,media,alta,urgente',
        ];

        $messages = [
            'nombre.required' => 'El nombre del tipo de notificación es obligatorio.',
            'nombre.unique' => 'Ya existe un tipo de notificación con ese nombre.',
            'nombre.max' => 'El nombre no puede exceder 255 caracteres.',
            'prioridad.required' => 'La prioridad es obligatoria.',
            'prioridad.in' => 'La prioridad debe ser: baja, media, alta o urgente.',
        ];

        try {
            $tipo = tipos_notificacion::find($id);

            if (!$tipo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de notificación no encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $tipo->update([
                'nombre' => $request->nombre,
                'prioridad' => $request->prioridad,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tipo de notificación actualizado exitosamente',
                'data' => $tipo
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el tipo de notificación',
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
            $tipo = tipos_notificacion::find($id);

            if (!$tipo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de notificación no encontrado'
                ], 404);
            }

            DB::beginTransaction();

            $tipo->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tipo de notificación eliminado exitosamente'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el tipo de notificación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByPriority($prioridad): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $tipos = tipos_notificacion::where('prioridad', $prioridad)->get();

            if ($tipos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "No se encontraron tipos de notificación con prioridad: {$prioridad}"
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $tipos
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los tipos de notificación por prioridad',
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
