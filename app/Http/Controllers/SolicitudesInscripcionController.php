<?php

namespace App\Http\Controllers;

use App\Models\solicitudes_inscripcion;
use App\RolesEnum;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SolicitudesInscripcionController extends Controller {
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
            $solicitudes = solicitudes_inscripcion::with(['estudiante', 'grupo.materia', 'respondidoPor'])->get();
            $data = $solicitudes->map(fn($s) => $this->transformSolicitud($s));
            return response()->json([
                'success' => true,
                'data' => $data
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
            $solicitud = solicitudes_inscripcion::with(['estudiante', 'grupo.materia', 'respondidoPor'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $this->transformSolicitud($solicitud)
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

        $user_rolName = $this->getUserRoleName();
        $rolesPermitidos = [
            RolesEnum::ROOT->value,
            RolesEnum::COORDINADOR_CARRERAS->value,
            RolesEnum::DOCENTE->value,
            RolesEnum::ESTUDIANTE->value,
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
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

            $solicitud->load(['estudiante', 'grupo.materia', 'respondidoPor']);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud creada exitosamente',
                'data' => $this->transformSolicitud($solicitud)
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

        $user_rolName = $this->getUserRoleName();
        $rolesPermitidos = [
            RolesEnum::ROOT->value,
            RolesEnum::COORDINADOR_CARRERAS->value,
            RolesEnum::DOCENTE->value,
            RolesEnum::ESTUDIANTE->value,
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
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
            $solicitud->load(['estudiante', 'grupo.materia', 'respondidoPor']);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud actualizada exitosamente',
                'data' => $this->transformSolicitud($solicitud)
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
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user_rolName = $this->getUserRoleName();
        $rolesPermitidos = [
            RolesEnum::ADMINISTRADOR_ACADEMICO->value,
            RolesEnum::DOCENTE->value,
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
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
            $solicitudes = solicitudes_inscripcion::with(['grupo.materia', 'respondidoPor'])
                ->where('estudiante_id', $id)
                ->get();

            $data = $solicitudes->map(fn($s) => $this->transformSolicitud($s));

            return response()->json([
                'success' => true,
                'data' => $data
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
            $solicitudes = solicitudes_inscripcion::with(['estudiante', 'respondidoPor', 'grupo.materia'])
                ->where('grupo_id', $id)
                ->get();

            $data = $solicitudes->map(fn($s) => $this->transformSolicitud($s));

            return response()->json([
                'success' => true,
                'data' => $data
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

        $estado = $this->sanitizeInput($estado);

        try {
            $solicitudes = solicitudes_inscripcion::with(['estudiante', 'grupo.materia', 'respondidoPor'])
                ->where('estado', $estado)
                ->get();

            $data = $solicitudes->map(fn($s) => $this->transformSolicitud($s));

            return response()->json([
                'success' => true,
                'data' => $data
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

        $tipo = $this->sanitizeInput($tipo);

        try {
            $solicitudes = solicitudes_inscripcion::with(['estudiante', 'grupo.materia', 'respondidoPor'])
                ->where('tipo_solicitud', $tipo)
                ->get();

            $data = $solicitudes->map(fn($s) => $this->transformSolicitud($s));

            return response()->json([
                'success' => true,
                'data' => $data
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
            $solicitud = solicitudes_inscripcion::findOrFail($id);

            $solicitud->update([
                'estado' => 'aceptada',
                'respondido_por' => auth()->id()
            ]);

            $solicitud->load(['estudiante', 'grupo.materia', 'respondidoPor']);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud aceptada exitosamente',
                'data' => $this->transformSolicitud($solicitud)
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
            $solicitud = solicitudes_inscripcion::findOrFail($id);

            $solicitud->update([
                'estado' => 'rechazada',
                'respondido_por' => auth()->id()
            ]);

            $solicitud->load(['estudiante', 'grupo.materia', 'respondidoPor']);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud rechazada exitosamente',
                'data' => $this->transformSolicitud($solicitud)
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
            $solicitudes = solicitudes_inscripcion::with(['estudiante', 'grupo.materia'])
                ->whereHas('grupo', function ($query) use ($id) {
                    $query->where('docente_id', $id);
                })
                ->where('estado', 'pendiente')
                ->get();

            $data = $solicitudes->map(fn($s) => $this->transformSolicitud($s));

            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las solicitudes pendientes del docente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // --- Nuevo método helper para transformar y normalizar una solicitud ---
    private function transformSolicitud($s): array {
        $estudiante = $s->estudiante ?? null;
        $grupo = $s->grupo ?? null;
        $materia = $grupo->materia ?? null;

        $estudiante_nombre = $estudiante->nombre_completo ?? $estudiante->name ?? ('#' . ($s->estudiante_id ?? 'N/A'));
        // Construir nombre de grupo (ej: "Programación - Grupo 1")
        $materia_nombre = $materia->nombre ?? null;
        $grupo_label = null;
        if ($materia_nombre) {
            $grupo_label = "{$materia_nombre} - Grupo " . ($grupo->numero_grupo ?? $grupo->id ?? '');
        } else {
            $grupo_label = "Grupo " . ($grupo->numero_grupo ?? $grupo->id ?? '');
        }

        return [
            'id' => $s->id,
            'estudiante_id' => $s->estudiante_id,
            'estudiante_nombre' => $estudiante_nombre,
            'grupo_id' => $s->grupo_id,
            'grupo_nombre' => $grupo_label,
            'materia_nombre' => $materia_nombre,
            'tipo_solicitud' => $this->normalizeText($s->tipo_solicitud),
            'estado' => $this->normalizeText($s->estado),
            'mensaje' => $s->mensaje,
            'motivo_rechazo' => $s->motivo_rechazo,
            'respondido_por' => $s->respondido_por,
            'created_at' => $s->created_at,
            'updated_at' => $s->updated_at,
        ];
    }

    private function normalizeText($text): string {
        $t = str_replace('_', ' ', (string)$text);
        // usar multibyte para capitalizar correctamente
        $t = mb_convert_case(mb_strtolower($t, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        return $t;
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
