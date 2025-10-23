<?php

namespace App\Http\Controllers;

use App\Models\asistencias_estudiantes;
use App\RolesEnum;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AsistenciasEstudiantesController extends Controller {
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
            $asistencias = asistencias_estudiantes::with(['sesionClase', 'estudiante'])->get();

            if ($asistencias->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron asistencias'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $asistencias
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las asistencias',
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
            $asistencia = asistencias_estudiantes::with(['sesionClase', 'estudiante'])->find($id);

            if (!$asistencia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Asistencia no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $asistencia
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la asistencia',
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
            RolesEnum::DOCENTE->value,
            RolesEnum::ESTUDIANTE->value
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        $request->merge([
            'estado' => $this->sanitizeInput($request->input('estado')),
        ]);

        try {
            $validator = Validator::make($request->all(), [
                'sesion_clase_id' => 'required|exists:sesiones_clases,id',
                'estudiante_id' => 'required|exists:users,id',
                'estado' => 'required|in:presente,tarde,ausente',
                'validado_por_qr' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar si ya existe un registro de asistencia
            $existente = asistencias_estudiantes::where('sesion_clase_id', $request->sesion_clase_id)
                ->where('estudiante_id', $request->estudiante_id)
                ->first();

            if ($existente) {
                return response()->json([
                    'success' => false,
                    'message' => 'La asistencia ya fue registrada para este estudiante en esta sesión'
                ], 409);
            }

            $asistencia = asistencias_estudiantes::create([
                'sesion_clase_id' => $request->sesion_clase_id,
                'estudiante_id' => $request->estudiante_id,
                'hora_registro' => Carbon::now(),
                'estado' => $request->estado,
                'validado_por_qr' => $request->validado_por_qr ?? false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Asistencia registrada exitosamente',
                'data' => $asistencia
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la asistencia',
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
            RolesEnum::ADMINISTRADOR_ACADEMICO->value,
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
            $asistencia = asistencias_estudiantes::find($id);

            if (!$asistencia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Asistencia no encontrada'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'sesion_clase_id' => 'sometimes|exists:sesiones_clases,id',
                'estudiante_id' => 'sometimes|exists:users,id',
                'estado' => 'sometimes|in:presente,tarde,ausente',
                'validado_por_qr' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $asistencia->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Asistencia actualizada exitosamente',
                'data' => $asistencia
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la asistencia',
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
            $asistencia = asistencias_estudiantes::find($id);

            if (!$asistencia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Asistencia no encontrada'
                ], 404);
            }

            $asistencia->delete();

            return response()->json([
                'success' => true,
                'message' => 'Asistencia eliminada exitosamente'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la asistencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getBySession($id): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user_rolName = $this->getUserRoleName();
        $rolesPermitidos = [
            RolesEnum::ADMINISTRADOR_ACADEMICO->value,
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
            $asistencias = asistencias_estudiantes::with(['estudiante'])
                ->where('sesion_clase_id', $id)
                ->get();

            if ($asistencias->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron asistencias para esta sesión'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $asistencias
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las asistencias de la sesión',
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
            RolesEnum::ADMINISTRADOR_ACADEMICO->value,
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
            $asistencias = asistencias_estudiantes::with(['sesionClase.horario.grupo'])
                ->where('estudiante_id', $id)
                ->get();

            if ($asistencias->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron asistencias para este estudiante'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $asistencias
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las asistencias del estudiante',
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
            RolesEnum::ADMINISTRADOR_ACADEMICO->value,
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
            $asistencias = asistencias_estudiantes::with(['sesionClase', 'estudiante'])
                ->where('estado', $estado)
                ->get();

            if ($asistencias->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "No se encontraron asistencias con estado: {$estado}"
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $asistencias
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las asistencias por estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function registerAttendance(Request $request): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user_rolName = $this->getUserRoleName();
        $rolesPermitidos = [
            RolesEnum::ADMINISTRADOR_ACADEMICO->value,
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
            $validator = Validator::make($request->all(), [
                'sesion_clase_id' => 'required|exists:sesiones_clases,id',
                'estudiante_id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar si ya existe un registro de asistencia
            $existente = asistencias_estudiantes::where('sesion_clase_id', $request->sesion_clase_id)
                ->where('estudiante_id', $request->estudiante_id)
                ->first();

            if ($existente) {
                return response()->json([
                    'success' => false,
                    'message' => 'La asistencia ya fue registrada para este estudiante en esta sesión'
                ], 409);
            }

            $asistencia = asistencias_estudiantes::create([
                'sesion_clase_id' => $request->sesion_clase_id,
                'estudiante_id' => $request->estudiante_id,
                'hora_registro' => Carbon::now(),
                'estado' => 'presente',
                'validado_por_qr' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Asistencia registrada exitosamente',
                'data' => $asistencia
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la asistencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAttendanceReport($student_id, $group_id): JsonResponse {
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
            $asistencias = asistencias_estudiantes::with(['sesionClase'])
                ->where('estudiante_id', $student_id)
                ->whereHas('sesionClase.horario', function ($query) use ($group_id) {
                    $query->where('grupo_id', $group_id);
                })
                ->get();

            if ($asistencias->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron asistencias para este estudiante en este grupo'
                ], 404);
            }

            $total = $asistencias->count();
            $presentes = $asistencias->where('estado', 'presente')->count();
            $tardes = $asistencias->where('estado', 'tarde')->count();
            $ausentes = $asistencias->where('estado', 'ausente')->count();
            $porcentaje = $total > 0 ? round(($presentes / $total) * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'asistencias' => $asistencias,
                    'resumen' => [
                        'total' => $total,
                        'presentes' => $presentes,
                        'tardes' => $tardes,
                        'ausentes' => $ausentes,
                        'porcentaje_asistencia' => $porcentaje
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el reporte de asistencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStudentStatistics($student_id): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user_rol = $this->getUserRole();

        $rolesPermitidos = [1, 2, 3, 4, 5];

        if (!in_array($user_rol, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $asistencias = asistencias_estudiantes::where('estudiante_id', $student_id)->get();

            if ($asistencias->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron asistencias para este estudiante'
                ], 404);
            }

            $total = $asistencias->count();
            $presentes = $asistencias->where('estado', 'presente')->count();
            $tardes = $asistencias->where('estado', 'tarde')->count();
            $ausentes = $asistencias->where('estado', 'ausente')->count();
            $porcentaje = $total > 0 ? round(($presentes / $total) * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'presentes' => $presentes,
                    'tardes' => $tardes,
                    'ausentes' => $ausentes,
                    'porcentaje_asistencia' => $porcentaje
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas del estudiante',
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
