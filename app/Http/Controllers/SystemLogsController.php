<?php

namespace App\Http\Controllers;

use App\Models\system_logs;
use App\RolesEnum;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SystemLogsController extends Controller {
    public function index(): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user_rolName = $this->getUserRoleName();

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
            $logs = system_logs::with(['usuario'])->get();

            if ($logs->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron registros en los logs del sistema'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $logs
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los logs del sistema',
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
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $log = system_logs::with(['usuario'])->find($id);

            if (!$log) {
                return response()->json([
                    'success' => false,
                    'message' => 'Log no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $log
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el log',
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

        $request->merge([
            'nivel' => $this->sanitizeInput($request->input('nivel')),
            'modulo' => $this->sanitizeInput($request->input('modulo')),
            'accion' => $this->sanitizeInput($request->input('accion')),
            'usuario_id' => $this->sanitizeInput($request->input('usuario_id')),
        ]);

        $rules = [
            'nivel' => 'required|in:DEBUG,INFO,WARNING,ERROR,CRITICAL',
            'modulo' => 'required|string|max:100',
            'accion' => 'required|string|max:255',
            'usuario_id' => 'required|exists:users,id',
            'contexto' => 'nullable|array',
        ];

        $messages = [
            'nivel.required' => 'El nivel del log es obligatorio.',
            'nivel.in' => 'El nivel debe ser: DEBUG, INFO, WARNING, ERROR o CRITICAL.',
            'modulo.required' => 'El módulo es obligatorio.',
            'modulo.max' => 'El módulo no puede exceder 100 caracteres.',
            'accion.required' => 'La acción es obligatoria.',
            'accion.max' => 'La acción no puede exceder 255 caracteres.',
            'usuario_id.required' => 'El ID del usuario es obligatorio.',
            'usuario_id.exists' => 'El usuario especificado no existe.',
            'contexto.array' => 'El contexto debe ser un objeto JSON válido.',
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

            $log = system_logs::create([
                'nivel' => $request->nivel,
                'modulo' => $request->modulo,
                'accion' => $request->accion,
                'usuario_id' => $request->usuario_id,
                'contexto' => $request->contexto,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Log registrado exitosamente',
                'data' => $log
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el log',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByLevel($nivel): JsonResponse {
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
            $logs = system_logs::with(['usuario'])
                ->where('nivel', strtoupper($nivel))
                ->orderBy('created_at', 'desc')
                ->get();

            if ($logs->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "No se encontraron logs de nivel: {$nivel}"
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $logs
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los logs por nivel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByModule($modulo): JsonResponse {
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
            $logs = system_logs::with(['usuario'])
                ->where('modulo', $modulo)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($logs->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "No se encontraron logs del módulo: {$modulo}"
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $logs
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los logs por módulo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByUser($id): JsonResponse {
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
            $logs = system_logs::where('usuario_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($logs->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron logs para este usuario'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $logs
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los logs del usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getErrors(): JsonResponse {
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
            $logs = system_logs::with(['usuario'])
                ->whereIn('nivel', ['ERROR', 'CRITICAL'])
                ->orderBy('created_at', 'desc')
                ->get();

            if ($logs->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron errores en los logs'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $logs
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los logs de errores',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getRecent(): JsonResponse {
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
            $logs = system_logs::with(['usuario'])
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();

            if ($logs->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron logs recientes'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $logs
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los logs recientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByDateRange(Request $request): JsonResponse {
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
            $validator = Validator::make($request->all(), [
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $logs = system_logs::with(['usuario'])
                ->whereBetween('created_at', [$request->fecha_inicio, $request->fecha_fin])
                ->orderBy('created_at', 'desc')
                ->get();

            if ($logs->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron logs en el rango de fechas especificado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $logs
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los logs por rango de fecha',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteOldLogs(Request $request): JsonResponse {
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
            $validator = Validator::make($request->all(), [
                'dias' => 'required|integer|min:30',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $fecha_limite = now()->subDays($request->dias);

            $deleted = system_logs::where('created_at', '<', $fecha_limite)->delete();

            return response()->json([
                'success' => true,
                'message' => "Se eliminaron {$deleted} registros de logs antiguos",
                'registros_eliminados' => $deleted
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar logs antiguos',
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
