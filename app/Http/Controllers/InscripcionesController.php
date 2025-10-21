<?php

namespace App\Http\Controllers;

use App\Models\inscripciones;
use App\RolesEnum;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InscripcionesController extends Controller {
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
            $inscripciones = inscripciones::with(['estudiante', 'grupo'])->get();

            if ($inscripciones->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron inscripciones'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $inscripciones
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las inscripciones',
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
            $inscripcion = inscripciones::with(['estudiante', 'grupo'])->find($id);

            if (!$inscripcion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inscripción no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $inscripcion
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la inscripción',
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

        $request->merge([
            'estudiante_id' => $this->sanitizeInput($request->input('estudiante_id')),
            'grupo_id' => $this->sanitizeInput($request->input('grupo_id')),
        ]);

        $rules = [
            'estudiante_id' => 'required|exists:users,id',
            'grupo_id' => 'required|exists:grupos,id',
        ];

        $messages = [
            'estudiante_id.required' => 'El campo estudiante_id es obligatorio.',
            'estudiante_id.exists' => 'El estudiante_id proporcionado no existe.',
            'grupo_id.required' => 'El campo grupo_id es obligatorio.',
            'grupo_id.exists' => 'El grupo_id proporcionado no existe.',
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

            // Verificar que no exista una inscripción activa
            $existente = inscripciones::where('estudiante_id', $request->estudiante_id)
                ->where('grupo_id', $request->grupo_id)
                ->where('estado', 'activo')
                ->first();

            if ($existente) {
                return response()->json([
                    'success' => false,
                    'message' => 'El estudiante ya está inscrito en este grupo'
                ], 409);
            }

            $inscripcion = inscripciones::create([
                'estudiante_id' => $request->estudiante_id,
                'grupo_id' => $request->grupo_id,
                'estado' => 'activo'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inscripción creada exitosamente',
                'data' => $inscripcion
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la inscripción',
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

        $request->merge([
            'estudiante_id' => $this->sanitizeInput($request->input('estudiante_id')),
            'grupo_id' => $this->sanitizeInput($request->input('grupo_id')),
            'estado' => $this->sanitizeInput($request->input('estado')),
        ]);

        $rules = [
            'estudiante_id' => 'sometimes|exists:users,id',
            'grupo_id' => 'sometimes|exists:grupos,id',
            'estado' => 'sometimes|in:activo,retirado,finalizado'
        ];

        $messages = [
            'estudiante_id.exists' => 'El estudiante_id proporcionado no existe.',
            'grupo_id.exists' => 'El grupo_id proporcionado no existe.',
            'estado.in' => 'El estado debe ser uno de los siguientes: activo, retirado, finalizado.'
        ];

        try {
            $inscripcion = inscripciones::find($id);

            if (!$inscripcion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inscripción no encontrada'
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

            $estudiante_id = $request->estudiante_id ?? $inscripcion->estudiante_id;
            $grupo_id = $request->grupo_id ?? $inscripcion->grupo_id;

            $duplicado = inscripciones::where('estudiante_id', $estudiante_id)
                ->where('grupo_id', $grupo_id)
                ->where('estado', 'activo')
                ->where('id', '!=', $id)
                ->first();

            if ($duplicado) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una inscripción activa para este estudiante en este grupo'
                ], 409);
            }

            $inscripcion->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Inscripción actualizada exitosamente',
                'data' => $inscripcion
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la inscripción',
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
            $inscripcion = inscripciones::find($id);

            if (!$inscripcion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inscripción no encontrada'
                ], 404);
            }

            $inscripcion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Inscripción eliminada exitosamente'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la inscripción',
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
            $inscripciones = inscripciones::with(['grupo.materia', 'grupo.docente'])
                ->where('estudiante_id', $id)
                ->get();

            if ($inscripciones->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron inscripciones para este estudiante'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $inscripciones
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las inscripciones del estudiante',
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
            $inscripciones = inscripciones::with(['estudiante'])
                ->where('grupo_id', $id)
                ->get();

            if ($inscripciones->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron inscripciones para este grupo'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $inscripciones
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las inscripciones del grupo',
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

        try {
            $inscripciones = inscripciones::with(['estudiante', 'grupo'])
                ->where('estado', $estado)
                ->get();

            if ($inscripciones->isEmpty()) {
                return response()->json([
                    'message' => "No se encontraron inscripciones con el estado: {$estado}"
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $inscripciones
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las inscripciones por estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function withdrawEnrollment(Request $request, $id): JsonResponse {
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
            $inscripcion = inscripciones::find($id);

            if (!$inscripcion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inscripción no encontrada'
                ], 404);
            }

            // Validar que la inscripción esté activa
            if ($inscripcion->estado !== 'activo') {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede retirar una inscripción con estado: {$inscripcion->estado}. Solo se pueden retirar inscripciones activas."
                ], 400);
            }

            $inscripcion->update([
                'estado' => 'retirado'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inscripción retirada exitosamente',
                'data' => $inscripcion
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al retirar la inscripción',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getActiveByStudent($student_id): JsonResponse {
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
            $inscripciones = inscripciones::with(['grupo.materia', 'grupo.docente', 'grupo.ciclo'])
                ->where('estudiante_id', $student_id)
                ->where('estado', 'activo')
                ->get();

            if ($inscripciones->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron inscripciones activas para este estudiante'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $inscripciones
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las inscripciones activas del estudiante',
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
