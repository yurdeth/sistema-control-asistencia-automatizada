<?php

namespace App\Http\Controllers;

use App\Models\sesiones_clase;
use App\RolesEnum;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SesionesClaseController extends Controller {
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
            $sesiones = Cache::remember('sesiones_clase_all', 60, function () {
                return sesiones_clase::with(['horario.grupo', 'horario.aula'])->get();
            });
            return response()->json([
                'success' => true,
                'data' => $sesiones
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las sesiones de clase',
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
            $sesion = sesiones_clase::with(['horario.grupo', 'horario.aula'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $sesion
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión no encontrada',
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

        Log::info($request);

        $request->merge([
            'horario_id' => $this->sanitizeInput($request->input('horario_id')),
            'fecha_clase' => $this->sanitizeInput($request->input('fecha_clase')),
        ]);

        $rules = [
            'horario_id' => 'required|exists:horarios,id',
            'fecha_clase' => 'required|date|after_or_equal:today',
        ];

        $messages = [
            'horario_id.required' => 'El campo horario_id es obligatorio.',
            'horario_id.exists' => 'El horario especificado no existe.',
            'fecha_clase.required' => 'El campo fecha_clase es obligatorio.',
            'fecha_clase.date' => 'El campo fecha_clase debe ser una fecha válida.',
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

            $sesion = sesiones_clase::create([
                'horario_id' => $request->horario_id,
                'fecha_clase' => $request->fecha_clase,
                'estado' => 'programada'
            ]);

            // Cambiar estado de aula a 'ocupada' si es necesario

            Cache::forget('sesiones_clase_all');

            return response()->json([
                'success' => true,
                'message' => 'Sesión creada exitosamente',
                'data' => $sesion
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la sesión',
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
            'horario_id' => $this->sanitizeInput($request->input('horario_id')),
            'fecha_clase' => $this->sanitizeInput($request->input('fecha_clase')),
            'hora_inicio_real' => $this->sanitizeInput($request->input('hora_inicio_real')),
            'hora_fin_real' => $this->sanitizeInput($request->input('hora_fin_real')),
            'estado' => $this->sanitizeInput($request->input('estado')),
        ]);

        $rules = [
            'horario_id' => 'sometimes|exists:horarios,id',
            'fecha_clase' => 'sometimes|date',
            'hora_inicio_real' => 'sometimes|date_format:Y-m-d H:i:s',
            'hora_fin_real' => 'sometimes|date_format:Y-m-d H:i:s',
            'estado' => 'sometimes|in:programada,en_curso,finalizada,cancelada,sin_marcar_salida'
        ];

        $messages = [
            'horario_id.exists' => 'El horario especificado no existe.',
            'fecha_clase.date' => 'El campo fecha_clase debe ser una fecha válida.',
            'hora_inicio_real.date_format' => 'El campo hora_inicio_real debe tener el formato Y-m-d H:i:s.',
            'hora_fin_real.date_format' => 'El campo hora_fin_real debe tener el formato Y-m-d H:i:s.',
            'estado.in' => 'El estado debe ser uno de los siguientes: programada, en_curso, finalizada, cancelada, sin_marcar_salida.'
        ];

        try {
            $sesion = sesiones_clase::findOrFail($id);

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $sesion->update($request->all());

            Cache::forget('sesiones_clase_all');

            return response()->json([
                'success' => true,
                'message' => 'Sesión actualizada exitosamente',
                'data' => $sesion
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la sesión',
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
            $sesion = sesiones_clase::findOrFail($id);
            $sesion->delete();

            Cache::forget('sesiones_clase_all');

            return response()->json([
                'success' => true,
                'message' => 'Sesión eliminada exitosamente'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la sesión',
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
            $sesiones = sesiones_clase::with(['horario.aula'])
                ->whereHas('horario', function ($query) use ($id) {
                    $query->where('grupo_id', $id);
                })
                ->get();

            return response()->json([
                'success' => true,
                'data' => $sesiones
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las sesiones del grupo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getBySchedule($id): JsonResponse {
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
            $sesiones = sesiones_clase::where('horario_id', $id)->get();

            if ($sesiones->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron sesiones para este horario'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $sesiones
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las sesiones del horario',
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
            $sesiones = sesiones_clase::with(['horario.grupo', 'horario.aula'])
                ->where('estado', $estado)
                ->get();

            if ($sesiones->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "No se encontraron sesiones con estado: {$estado}"
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $sesiones
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las sesiones por estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function startSession(Request $request): JsonResponse {
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
            'horario_id' => $this->sanitizeInput($request->input('horario_id')),
            'fecha_clase' => $this->sanitizeInput($request->input('fecha_clase')),
        ]);

        $rules = [
            'horario_id' => 'required|exists:horarios,id',
            'fecha_clase' => 'required|date',
        ];

        $messages = [
            'horario_id.required' => 'El campo horario_id es obligatorio.',
            'horario_id.exists' => 'El horario especificado no existe.',
            'fecha_clase.required' => 'El campo fecha_clase es obligatorio.',
            'fecha_clase.date' => 'El campo fecha_clase debe ser una fecha válida.',
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

            $sesionExistente = sesiones_clase::where('horario_id', $request->horario_id)
                ->where('fecha_clase', $request->fecha_clase)
                ->where('estado', 'en_curso')
                ->first();

            if ($sesionExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una sesión en curso para este horario y fecha'
                ], 409);
            }

            $sesion = sesiones_clase::create([
                'horario_id' => $request->horario_id,
                'fecha_clase' => $request->fecha_clase,
                'hora_inicio_real' => Carbon::now(),
                'estado' => 'en_curso'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sesión iniciada exitosamente',
                'data' => $sesion
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar la sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function finishSession(Request $request, $id): JsonResponse {
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
            $sesion = sesiones_clase::find($id);

            if (!$sesion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesión no encontrada'
                ], 404);
            }

            if ($sesion->estado !== 'en_curso') {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede finalizar una sesión con estado: {$sesion->estado}. Solo se pueden finalizar sesiones en curso."
                ], 400);
            }

            $sesion->update([
                'hora_fin_real' => Carbon::now(),
                'estado' => 'finalizada'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sesión finalizada exitosamente',
                'data' => $sesion
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al finalizar la sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getTodayByProfessor($id): JsonResponse {
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
            $today = Carbon::today();
            $sesiones = sesiones_clase::with(['horario.grupo', 'horario.aula'])
                ->whereHas('horario.grupo', function ($query) use ($id) {
                    $query->where('docente_id', $id);
                })
                ->whereDate('fecha_clase', $today)
                ->get();

            if ($sesiones->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El docente no tiene sesiones programadas para hoy'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $sesiones
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las sesiones de hoy del docente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByDate($fecha): JsonResponse {
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
            $validator = Validator::make(['fecha' => $fecha], [
                'fecha' => 'required|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formato de fecha inválido',
                    'errors' => $validator->errors()
                ], 422);
            }

            $sesiones = sesiones_clase::with(['horario.grupo', 'horario.aula'])
                ->whereDate('fecha_clase', $fecha)
                ->get();

            if ($sesiones->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "No se encontraron sesiones para la fecha: {$fecha}"
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $sesiones
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las sesiones por fecha',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function changeStatus(Request $request, $id): JsonResponse {
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
            'estado' => $this->sanitizeInput($request->input('estado')),
        ]);

        $rules = [
            'estado' => 'required|in:programada,en_curso,finalizada,cancelada,sin_marcar_salida'
        ];

        $messages = [
            'estado.required' => 'El campo estado es obligatorio.',
            'estado.in' => 'El estado debe ser uno de los siguientes: programada, en_curso, finalizada, cancelada, sin_marcar_salida.'
        ];

        try {
            $sesion = sesiones_clase::findOrFail($id);

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $sesion->update(['estado' => $request->estado]);

            return response()->json([
                'success' => true,
                'message' => 'Estado de la sesión actualizado exitosamente',
                'data' => $sesion
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado de la sesión',
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
