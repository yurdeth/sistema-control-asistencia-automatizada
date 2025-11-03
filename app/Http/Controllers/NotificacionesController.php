<?php

namespace App\Http\Controllers;

use App\Models\notificaciones;
use App\RolesEnum;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NotificacionesController extends Controller {
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
            $notificaciones = notificaciones::with(['tipoNotificacion', 'usuarioDestino'])->get();

            if ($notificaciones->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron notificaciones'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $notificaciones
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las notificaciones',
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
            $notificacion = notificaciones::with(['tipoNotificacion', 'usuarioDestino'])->find($id);

            if (!$notificacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notificación no encontrada'
                ], 404);
            }

            $user_rolName = $this->getUserRoleName();
            $rolesPermitidos = [
                RolesEnum::ROOT->value,
                RolesEnum::ADMINISTRADOR_ACADEMICO->value,
                RolesEnum::JEFE_DEPARTAMENTO->value,
                RolesEnum::COORDINADOR_CARRERAS->value,
                RolesEnum::DOCENTE->value,
            ];

            if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos) && $notificacion->usuario_destino_id !== Auth::id()) {
                return response()->json([
                    'message' => 'Acceso no autorizado',
                    'success' => false
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $notificacion
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la notificación',
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
            'tipo_notificacion_id' => $this->sanitizeInput($request->input('tipo_notificacion_id')),
            'usuario_destino_id' => $this->sanitizeInput($request->input('usuario_destino_id')),
            'titulo' => $this->sanitizeInput($request->input('titulo')),
            'mensaje' => $this->sanitizeInput($request->input('mensaje')),
            'canal' => $this->sanitizeInput($request->input('canal')),
        ]);

        $rules = [
            'tipo_notificacion_id' => 'required|exists:tipos_notificacion,id',
            'usuario_destino_id' => 'required|exists:users,id',
            'titulo' => 'required|string|max:255',
            'mensaje' => 'required|string',
            'canal' => 'required|in:email,push',
        ];

        $messages = [
            'tipo_notificacion_id.required' => 'El tipo de notificación es obligatorio.',
            'tipo_notificacion_id.exists' => 'El tipo de notificación especificado no existe.',
            'usuario_destino_id.required' => 'El usuario destinatario es obligatorio.',
            'usuario_destino_id.exists' => 'El usuario destinatario especificado no existe.',
            'titulo.required' => 'El título es obligatorio.',
            'titulo.max' => 'El título no puede exceder 255 caracteres.',
            'mensaje.required' => 'El mensaje es obligatorio.',
            'canal.required' => 'El canal es obligatorio.',
            'canal.in' => 'El canal debe ser: email o push.',
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

            $notificacion = notificaciones::create([
                'tipo_notificacion_id' => $request->tipo_notificacion_id,
                'usuario_destino_id' => $request->usuario_destino_id,
                'titulo' => $request->titulo,
                'mensaje' => $request->mensaje,
                'canal' => $request->canal,
                'estado' => 'pendiente',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Notificación creada exitosamente',
                'data' => $notificacion
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la notificación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function markAsRead($id): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $notificacion = notificaciones::find($id);

            if (!$notificacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notificación no encontrada'
                ], 404);
            }

            DB::beginTransaction();

            $notificacion->update(['estado' => 'leida']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Notificación marcada como leída',
                'data' => $notificacion
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al marcar la notificación como leída',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMyNotifications(): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $notificaciones = notificaciones::with(['tipoNotificacion'])
                ->where('usuario_destino_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get();

            if ($notificaciones->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes notificaciones'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $notificaciones
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tus notificaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMyUnreadNotifications(): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $notificaciones = notificaciones::with(['tipoNotificacion'])
                ->where('usuario_destino_id', Auth::id())
                ->whereIn('estado', ['pendiente', 'enviada'])
                ->orderBy('created_at', 'desc')
                ->get();

            if ($notificaciones->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes notificaciones sin leer'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $notificaciones
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las notificaciones sin leer',
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
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos) && $id != Auth::id()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $notificaciones = notificaciones::with(['tipoNotificacion'])
                ->where('usuario_destino_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($notificaciones->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron notificaciones para este usuario'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $notificaciones
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las notificaciones del usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByStatus($estado): JsonResponse {
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
            $notificaciones = notificaciones::with(['tipoNotificacion', 'usuarioDestino'])
                ->where('estado', $estado)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($notificaciones->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "No se encontraron notificaciones con estado: {$estado}"
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $notificaciones
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las notificaciones por estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByType($tipo_id): JsonResponse {
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
            $notificaciones = notificaciones::with(['tipoNotificacion', 'usuarioDestino'])
                ->where('tipo_notificacion_id', $tipo_id)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($notificaciones->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron notificaciones de este tipo'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $notificaciones
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las notificaciones por tipo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPending(): JsonResponse {
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
            $notificaciones = notificaciones::with(['tipoNotificacion', 'usuarioDestino'])
                ->where('estado', 'pendiente')
                ->orderBy('created_at', 'asc')
                ->get();

            if ($notificaciones->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay notificaciones pendientes'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $notificaciones
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las notificaciones pendientes',
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
