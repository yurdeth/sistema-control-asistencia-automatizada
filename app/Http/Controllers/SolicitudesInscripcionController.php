<?php

namespace App\Http\Controllers;

use App\Models\solicitudes_inscripcion;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SolicitudesInscripcionController extends Controller {
    public function index(): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        if ($user_rol >= 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $solicitudes = solicitudes_inscripcion::with(['estudiante', 'grupo', 'respondidoPor'])->get();
            return response()->json([
                'success' => true,
                'data' => $solicitudes
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las solicitudes de inscripción',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        if ($user_rol >= 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $solicitud = solicitudes_inscripcion::with(['estudiante', 'grupo', 'respondidoPor'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $solicitud
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitud no encontrada',
                'error' => $e->getMessage()
            ], 404);
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
            'estudiante_id' => $this->sanitizeInput($request->input('estudiante_id')),
            'grupo_id' => $this->sanitizeInput($request->input('grupo_id')),
            'tipo_solicitud' => $this->sanitizeInput($request->input('tipo_solicitud')),
        ]);

        $rules = [
            'estudiante_id' => 'required|exists:users,id',
            'grupo_id' => 'required|exists:grupos,id',
            'tipo_solicitud' => 'required|in:estudiante_solicita,docente_invita',
        ];

        $messages = [
            'estudiante_id.required' => 'El campo estudiante_id es obligatorio.',
            'estudiante_id.exists' => 'El estudiante_id proporcionado no existe.',
            'grupo_id.required' => 'El campo grupo_id es obligatorio.',
            'grupo_id.exists' => 'El grupo_id proporcionado no existe.',
            'tipo_solicitud.required' => 'El campo tipo_solicitud es obligatorio.',
            'tipo_solicitud.in' => 'El campo tipo_solicitud debe ser estudiante_solicita o docente_invita.',
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

            $solicitud = solicitudes_inscripcion::create([
                'estudiante_id' => $request->estudiante_id,
                'grupo_id' => $request->grupo_id,
                'tipo_solicitud' => $request->tipo_solicitud,
                'estado' => 'pendiente'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud creada exitosamente',
                'data' => $solicitud
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function edit(Request $request, $id): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $request->merge([
            'estudiante_id' => $this->sanitizeInput($request->input('estudiante_id')),
            'grupo_id' => $this->sanitizeInput($request->input('grupo_id')),
            'tipo_solicitud' => $this->sanitizeInput($request->input('tipo_solicitud')),
            'estado' => $this->sanitizeInput($request->input('estado')),
        ]);

        $rules = [
            'estudiante_id' => 'sometimes|exists:users,id',
            'grupo_id' => 'sometimes|exists:grupos,id',
            'tipo_solicitud' => 'sometimes|in:estudiante_solicita,docente_invita',
            'estado' => 'sometimes|in:pendiente,aceptada,rechazada,cancelada'
        ];

        $messages = [
            'estudiante_id.exists' => 'El estudiante_id proporcionado no existe.',
            'grupo_id.exists' => 'El grupo_id proporcionado no existe.',
            'tipo_solicitud.in' => 'El campo tipo_solicitud debe ser estudiante_solicita o docente_invita.',
            'estado.in' => 'El campo estado debe ser pendiente, aceptada, rechazada o cancelada.'
        ];

        try {
            $solicitud = solicitudes_inscripcion::findOrFail($id);

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $solicitud->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Solicitud actualizada exitosamente',
                'data' => $solicitud
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        if ($user_rol >= 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $solicitud = solicitudes_inscripcion::findOrFail($id);
            $solicitud->delete();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud eliminada exitosamente'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByStudent($id): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        if ($user_rol >= 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $solicitudes = solicitudes_inscripcion::with(['grupo', 'respondidoPor'])
                ->where('estudiante_id', $id)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $solicitudes
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las solicitudes del estudiante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByGroup($id): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        if ($user_rol >= 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $solicitudes = solicitudes_inscripcion::with(['estudiante', 'respondidoPor'])
                ->where('grupo_id', $id)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $solicitudes
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las solicitudes del grupo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByStatus($estado): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        if ($user_rol >= 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        $estado = $this->sanitizeInput($estado);

        try {
            $solicitudes = solicitudes_inscripcion::with(['estudiante', 'grupo', 'respondidoPor'])
                ->where('estado', $estado)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $solicitudes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las solicitudes por estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByType($tipo): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        if ($user_rol >= 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        $tipo = $this->sanitizeInput($tipo);

        try {
            $solicitudes = solicitudes_inscripcion::with(['estudiante', 'grupo', 'respondidoPor'])
                ->where('tipo_solicitud', $tipo)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $solicitudes
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las solicitudes por tipo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function acceptRequest(Request $request, $id): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        if ($user_rol >= 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $solicitud = solicitudes_inscripcion::findOrFail($id);

            $solicitud->update([
                'estado' => 'aceptada',
                'respondido_por_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud aceptada exitosamente',
                'data' => $solicitud
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aceptar la solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function rejectRequest(Request $request, $id): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        if ($user_rol >= 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $solicitud = solicitudes_inscripcion::findOrFail($id);

            $solicitud->update([
                'estado' => 'rechazada',
                'respondido_por_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud rechazada exitosamente',
                'data' => $solicitud
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar la solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPendingByProfessor($id): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        if ($user_rol >= 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $solicitudes = solicitudes_inscripcion::with(['estudiante', 'grupo'])
                ->whereHas('grupo', function ($query) use ($id) {
                    $query->where('docente_id', $id);
                })
                ->where('estado', 'pendiente')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $solicitudes
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las solicitudes pendientes del docente',
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
}
