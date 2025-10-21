<?php

namespace App\Http\Controllers;

use App\Models\mantenimientos;
use App\RolesEnum;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MantenimientosController extends Controller {
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
            $mantenimientos = mantenimientos::with(['aula', 'usuarioRegistro'])->get();

            if ($mantenimientos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron mantenimientos'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $mantenimientos
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los mantenimientos',
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
            $mantenimiento = mantenimientos::with(['aula', 'usuarioRegistro'])->find($id);

            if (!$mantenimiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mantenimiento no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $mantenimiento
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el mantenimiento',
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
            'motivo' => $this->sanitizeInput($request->input('motivo')),
            'aula_id' => $this->sanitizeInput($request->input('aula_id')),
            'usuario_registro_id' => $this->sanitizeInput($request->input('usuario_registro_id')),
            'fecha_inicio' => $this->sanitizeInput($request->input('fecha_inicio')),
            'fecha_fin_programada' => $this->sanitizeInput($request->input('fecha_fin_programada'))
        ]);

        $rules = [
            'aula_id' => 'required|exists:aulas,id',
            'usuario_registro_id' => 'required|exists:users,id',
            'motivo' => 'required|string|max:500',
            'fecha_inicio' => 'required|date',
            'fecha_fin_programada' => 'required|date|after:fecha_inicio',
        ];

        $messages = [
            'aula_id.required' => 'El ID del aula es obligatorio.',
            'aula_id.exists' => 'El ID del aula no existe en la base de datos.',
            'usuario_registro_id.required' => 'El ID del usuario que registra es obligatorio.',
            'usuario_registro_id.exists' => 'El ID del usuario que registra no existe en la base de datos.',
            'motivo.required' => 'El motivo del mantenimiento es obligatorio.',
            'motivo.string' => 'El motivo debe ser una cadena de texto.',
            'motivo.max' => 'El motivo no debe exceder los 500 caracteres.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_fin_programada.required' => 'La fecha fin programada es obligatoria.',
            'fecha_fin_programada.date' => 'La fecha fin programada debe ser una fecha válida.',
            'fecha_fin_programada.after' => 'La fecha fin programada debe ser posterior a la fecha de inicio.'
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

            // Validar si hay un mantenimiento activo para la misma aula en las mismas fechas
            $conflicto = mantenimientos::where('aula_id', $request->aula_id)
                ->whereIn('estado', ['programado', 'en_proceso'])
                ->where(function ($query) use ($request) {
                    $query->whereBetween('fecha_inicio', [$request->fecha_inicio, $request->fecha_fin_programada])
                        ->orWhereBetween('fecha_fin_programada', [$request->fecha_inicio, $request->fecha_fin_programada]);
                })
                ->first();

            if ($conflicto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un mantenimiento programado para esta aula en el rango de fechas seleccionado'
                ], 409);
            }

            $mantenimiento = mantenimientos::create([
                'aula_id' => $request->aula_id,
                'usuario_registro_id' => $request->usuario_registro_id,
                'motivo' => $request->motivo,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin_programada' => $request->fecha_fin_programada,
                'estado' => 'programado'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento creado exitosamente',
                'data' => $mantenimiento
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el mantenimiento',
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
            'motivo' => $this->sanitizeInput($request->input('motivo')),
            'aula_id' => $this->sanitizeInput($request->input('aula_id')),
            'fecha_inicio' => $this->sanitizeInput($request->input('fecha_inicio')),
            'fecha_fin_programada' => $this->sanitizeInput($request->input('fecha_fin_programada')),
            'fecha_fin_real' => $this->sanitizeInput($request->input('fecha_fin_real')),
            'estado' => $this->sanitizeInput($request->input('estado'))
        ]);

        $rules = [
            'aula_id' => 'sometimes|exists:aulas,id',
            'motivo' => 'sometimes|string|max:500',
            'fecha_inicio' => 'sometimes|date',
            'fecha_fin_programada' => 'sometimes|date|after_or_equal:fecha_inicio',
            'fecha_fin_real' => 'sometimes|date|after_or_equal:fecha_inicio',
            'estado' => 'sometimes|in:programado,en_proceso,finalizado,cancelado'
        ];

        $messages = [
            'aula_id.exists' => 'El ID del aula no existe en la base de datos.',
            'motivo.string' => 'El motivo debe ser una cadena de texto.',
            'motivo.max' => 'El motivo no debe exceder los 500 caracteres.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_fin_programada.date' => 'La fecha fin programada debe ser una fecha válida.',
            'fecha_fin_programada.after_or_equal' => 'La fecha fin programada debe ser posterior o igual a la fecha de inicio.',
            'fecha_fin_real.date' => 'La fecha fin real debe ser una fecha válida.',
            'fecha_fin_real.after_or_equal' => 'La fecha fin real debe ser posterior o igual a la fecha de inicio.',
            'estado.in' => 'El estado debe ser uno de los siguientes: programado, en_proceso, finalizado, cancelado.'
        ];

        try {
            $mantenimiento = mantenimientos::find($id);

            if (!$mantenimiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mantenimiento no encontrado'
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


            $aula_id = $request->aula_id ?? $mantenimiento->aula_id;
            $fecha_inicio = $request->fecha_inicio ?? $mantenimiento->fecha_inicio;
            $fecha_fin = $request->fecha_fin_programada ?? $mantenimiento->fecha_fin_programada;

            $conflicto = mantenimientos::where('aula_id', $aula_id)
                ->where('id', '!=', $id)
                ->where('estado', '!=', 'cancelado')
                ->where(function ($query) use ($fecha_inicio, $fecha_fin) {
                    $query->whereBetween('fecha_inicio', [$fecha_inicio, $fecha_fin])
                        ->orWhereBetween('fecha_fin_programada', [$fecha_inicio, $fecha_fin])
                        ->orWhere(function ($q) use ($fecha_inicio, $fecha_fin) {
                            $q->where('fecha_inicio', '<=', $fecha_inicio)
                                ->where('fecha_fin_programada', '>=', $fecha_fin);
                        });
                })
                ->exists();

            if ($conflicto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un mantenimiento programado para esta aula en ese período'
                ], 422);
            }

            $mantenimiento->update($request->only([
                'aula_id',
                'motivo',
                'fecha_inicio',
                'fecha_fin_programada',
                'fecha_fin_real',
                'estado'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento actualizado exitosamente',
                'data' => $mantenimiento
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el mantenimiento',
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
            $mantenimiento = mantenimientos::find($id);

            if (!$mantenimiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mantenimiento no encontrado'
                ], 404);
            }

            $mantenimiento->delete();

            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento eliminado exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el mantenimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByClassroom($id): JsonResponse {
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
            $mantenimientos = mantenimientos::with(['usuarioRegistro'])
                ->where('aula_id', $id)
                ->get();

            if ($mantenimientos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron mantenimientos para esta aula'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $mantenimientos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los mantenimientos del aula',
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
            $mantenimientos = mantenimientos::with(['aula', 'usuarioRegistro'])
                ->where('estado', $estado)
                ->get();

            if ($mantenimientos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "No se encontraron mantenimientos con estado: {$estado}"
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $mantenimientos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los mantenimientos por estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUpcoming(): JsonResponse {
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
            $mantenimientos = mantenimientos::with(['aula', 'usuarioRegistro'])
                ->where('estado', 'programado')
                ->where('fecha_inicio', '>=', Carbon::today())
                ->orderBy('fecha_inicio', 'asc')
                ->get();

            if ($mantenimientos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay mantenimientos próximos programados'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $mantenimientos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los próximos mantenimientos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function finishMaintenance(Request $request, $id): JsonResponse {
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
            $mantenimiento = mantenimientos::find($id);

            if (!$mantenimiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mantenimiento no encontrado'
                ], 404);
            }

            // Validar que el mantenimiento esté en proceso
            if ($mantenimiento->estado !== 'en_proceso') {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede finalizar un mantenimiento con estado: {$mantenimiento->estado}. Solo se pueden finalizar mantenimientos en proceso."
                ], 400);
            }

            $mantenimiento->update([
                'fecha_fin_real' => Carbon::now(),
                'estado' => 'finalizado'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento finalizado exitosamente',
                'data' => $mantenimiento
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al finalizar el mantenimiento',
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

        $estado = $this->sanitizeInput($request->input('estado'));

        $rules = [
            'estado' => 'required|in:programado,en_proceso,finalizado,cancelado'
        ];

        $messages = [
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser uno de los siguientes: programado, en_proceso, finalizado, cancelado.'
        ];

        try {
            $mantenimiento = mantenimientos::find($id);

            if (!$mantenimiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mantenimiento no encontrado'
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

            $mantenimiento->update(['estado' => $estado]);

            return response()->json([
                'success' => true,
                'message' => 'Estado del mantenimiento actualizado exitosamente',
                'data' => $mantenimiento
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado del mantenimiento',
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
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after:fecha_inicio',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $mantenimientos = mantenimientos::with(['aula', 'usuarioRegistro'])
                ->whereBetween('fecha_inicio', [$request->fecha_inicio, $request->fecha_fin])
                ->get();

            if ($mantenimientos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron mantenimientos en el rango de fechas especificado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $mantenimientos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los mantenimientos por rango de fecha',
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
