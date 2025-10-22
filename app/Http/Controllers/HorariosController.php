<?php

namespace App\Http\Controllers;

use App\Models\grupos;
use App\Models\horarios;
use App\RolesEnum;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HorariosController extends Controller {

    public function index(): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user_rolName = $this->getUserRoleName();

        try {
            if ($user_rolName == RolesEnum::DOCENTE->value) {
                $horarios = Cache::remember('horarios_index', 60, function () {
                    return (new horarios())->getHorariosByProfessor();
                });
            } else {
                $horarios = Cache::remember('horarios_index_all', 60, function () {
                    return (new horarios())->getHorarios();
                });
            }

            if (!$horarios || $horarios->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron horarios',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'message' => 'Horarios obtenidos exitosamente',
                'data' => $horarios
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los horarios',
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
            $horario = (new horarios())->getHorariosById($id)->first();

            if (!$horario) {
                return response()->json([
                    'message' => 'Horario no encontrado',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Horario obtenido exitosamente',
                'success' => true,
                'data' => $horario
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el horario',
                'success' => false,
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
            RolesEnum::ADMINISTRADOR_ACADEMICO->value,
            RolesEnum::JEFE_DEPARTAMENTO->value,
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        $request->merge([
            'grupo_id' => $this->sanitizeInput($request->grupo_id),
            'aula_id' => $this->sanitizeInput($request->aula_id),
            'dia_semana' => $this->sanitizeInput($request->dia_semana),
            'hora_inicio' => $this->sanitizeInput($request->hora_inicio),
            'hora_fin' => $this->sanitizeInput($request->hora_fin)
        ]);

        $rules = [
            'grupo_id' => 'required|exists:grupos,id',
            'aula_id' => 'required|exists:aulas,id',
            'dia_semana' => 'required|in:Lunes,Martes,Miercoles,Jueves,Viernes,Sabado,Domingo',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio'
        ];

        $messages = [
            'grupo_id.required' => 'El campo grupo_id es obligatorio.',
            'grupo_id.exists' => 'El grupo especificado no existe.',
            'aula_id.required' => 'El campo aula_id es obligatorio.',
            'aula_id.exists' => 'El aula especificada no existe.',
            'dia_semana.required' => 'El campo dia_semana es obligatorio.',
            'dia_semana.in' => 'El campo dia_semana debe ser uno de los siguientes valores: Lunes, Martes, Miercoles, Jueves, Viernes, Sabado, Domingo.',
            'hora_inicio.required' => 'El campo hora_inicio es obligatorio.',
            'hora_inicio.date_format' => 'El campo hora_inicio debe tener el formato HH:MM.',
            'hora_fin.required' => 'El campo hora_fin es obligatorio.',
            'hora_fin.date_format' => 'El campo hora_fin debe tener el formato HH:MM.',
            'hora_fin.after' => 'El campo hora_fin debe ser una hora posterior a hora_inicio.'
        ];

        try {
            $validation = $request->validate($rules, $messages);

            $conflicto = horarios::where('aula_id', $request->aula_id)
                ->where('dia_semana', $request->dia_semana)
                ->where(function ($query) use ($request) {
                    $query->whereBetween('hora_inicio', [$request->hora_inicio, $request->hora_fin])
                        ->orWhereBetween('hora_fin', [$request->hora_inicio, $request->hora_fin])
                        ->orWhere(function ($q) use ($request) {
                            $q->where('hora_inicio', '<=', $request->hora_inicio)
                                ->where('hora_fin', '>=', $request->hora_fin);
                        });
                })
                ->exists();

            if ($conflicto) {
                return response()->json([
                    'message' => 'Conflicto de horario detectado',
                    'success' => false
                ], 409);
            }

            DB::beginTransaction();

            $horario = horarios::create($validation);

            DB::commit();

            return response()->json([
                'message' => 'Horario creado exitosamente',
                'success' => true,
                'data' => $horario
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear el horario',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function sanitizeInput($input): string {
        return htmlspecialchars(strip_tags(trim($input)));
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
            RolesEnum::JEFE_DEPARTAMENTO->value,
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        $request->merge([
            'grupo_id' => $this->sanitizeInput($request->grupo_id),
            'aula_id' => $this->sanitizeInput($request->aula_id),
            'dia_semana' => $this->sanitizeInput($request->dia_semana),
            'hora_inicio' => $this->sanitizeInput($request->hora_inicio),
            'hora_fin' => $this->sanitizeInput($request->hora_fin)
        ]);

        $rules = [
            'grupo_id' => 'sometimes|exists:grupos,id',
            'aula_id' => 'sometimes|exists:aulas,id',
            'dia_semana' => 'sometimes|in:Lunes,Martes,Miercoles,Jueves,Viernes,Sabado,Domingo',
            'hora_inicio' => 'sometimes|date_format:H:i',
            'hora_fin' => 'sometimes|date_format:H:i|after:hora_inicio'
        ];

        $messages = [
            'grupo_id.exists' => 'El grupo especificado no existe.',
            'aula_id.exists' => 'El aula especificada no existe.',
            'dia_semana.in' => 'El campo dia_semana debe ser uno de los siguientes valores: Lunes, Martes, Miercoles, Jueves, Viernes, Sabado, Domingo.',
            'hora_inicio.date_format' => 'El campo hora_inicio debe tener el formato HH:MM.',
            'hora_fin.date_format' => 'El campo hora_fin debe tener el formato HH:MM.',
            'hora_fin.after' => 'El campo hora_fin debe ser una hora posterior a hora_inicio.'
        ];

        try {
            $validation = $request->validate($rules, $messages);

            $horario = horarios::find($id);

            if (!$horario) {
                return response()->json([
                    'message' => 'Horario no encontrado',
                    'success' => false
                ], 404);
            }

            if (isset($validation['aula_id']) || isset($validation['dia_semana']) || isset($validation['hora_inicio']) || isset($validation['hora_fin'])) {
                $aula_id = $validation['aula_id'] ?? $horario->aula_id;
                $dia_semana = $validation['dia_semana'] ?? $horario->dia_semana;
                $hora_inicio = $validation['hora_inicio'] ?? $horario->hora_inicio;
                $hora_fin = $validation['hora_fin'] ?? $horario->hora_fin;

                $conflicto = horarios::where('aula_id', $aula_id)
                    ->where('dia_semana', $dia_semana)
                    ->where('id', '!=', $id)
                    ->where(function ($query) use ($hora_inicio, $hora_fin) {
                        $query->whereBetween('hora_inicio', [$hora_inicio, $hora_fin])
                            ->orWhereBetween('hora_fin', [$hora_inicio, $hora_fin])
                            ->orWhere(function ($q) use ($hora_inicio, $hora_fin) {
                                $q->where('hora_inicio', '<=', $hora_inicio)
                                    ->where('hora_fin', '>=', $hora_fin);
                            });
                    })
                    ->exists();

                if ($conflicto) {
                    return response()->json([
                        'message' => 'Conflicto de horario detectado',
                        'success' => false
                    ], 409);
                }
            }

            DB::beginTransaction();
            $horario->update($validation);
            DB::commit();

            return response()->json([
                'message' => 'Horario actualizado exitosamente',
                'success' => true,
                'data' => $horario
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error de validación',
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
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
            RolesEnum::JEFE_DEPARTAMENTO->value,
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $horario = horarios::where('id', $id)->lockForUpdate()->first();

            if (!$horario) {
                return response()->json([
                    'message' => 'Horario no encontrado',
                    'success' => false
                ], 404);
            }

            DB::beginTransaction();
            $horario->delete();
            DB::commit();

            return response()->json([
                'message' => 'Horario eliminado exitosamente',
                'success' => true
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar el horario',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSchedulesByGroup($grupo_id): JsonResponse {
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
            $horarios = Cache::remember('horarios_grupo_' . $grupo_id, 60, function () use ($grupo_id) {
                return (new horarios())->getHorariosByGroup($grupo_id);
            });

            if ($horarios->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron horarios para el grupo especificado',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Horarios obtenidos exitosamente',
                'data' => $horarios
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los horarios por grupo',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSchedulesByClassroom($aula_id): JsonResponse {
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
            $horarios = Cache::remember('horarios_aula_' . $aula_id, 60, function () use ($aula_id) {
                return (new horarios())->getHorariosByClassroom($aula_id);
            });

            if ($horarios->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron horarios para el aula especificada',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Horarios obtenidos exitosamente',
                'success' => true,
                'data' => $horarios
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los horarios por aula',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSchedulesByDay($dia_semana): JsonResponse {
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

            $dia_semana = $this->sanitizeInput($dia_semana);
            $horarios = Cache::remember('horarios_dia_' . $dia_semana, 60, function () use ($dia_semana) {
                return (new horarios())->getHorariosByDay($dia_semana);
            });

            if ($horarios->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron horarios para el día especificado',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Horarios obtenidos exitosamente',
                'success' => true,
                'data' => $horarios
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los horarios por día',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getScheduleConflicts(int $grupo_id): JsonResponse {
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
            $grupo = grupos::find($grupo_id);

            if (!$grupo) {
                return response()->json([
                    'message' => 'Grupo no encontrado',
                    'success' => false
                ], 404);
            }

            $conflictos = DB::table('horarios as h1')
                ->join('grupos as g1', 'h1.grupo_id', '=', 'g1.id')
                ->join('horarios as h2', function ($join) use ($grupo_id) {
                    $join->on('h1.dia_semana', '=', 'h2.dia_semana')
                        ->where('h2.grupo_id', '=', $grupo_id);
                })
                ->join('grupos as g2', 'h2.grupo_id', '=', 'g2.id')
                ->where('g1.docente_id', $grupo->docente_id)
                ->where('h1.grupo_id', '!=', $grupo_id)
                ->where(function ($query) {
                    $query->whereBetween('h1.hora_inicio', [DB::raw('h2.hora_inicio'), DB::raw('h2.hora_fin')])
                        ->orWhereBetween('h1.hora_fin', [DB::raw('h2.hora_inicio'), DB::raw('h2.hora_fin')]);
                })
                ->select('h1.*', 'g1.numero_grupo')
                ->get();

            if ($conflictos->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron conflictos de horario para el grupo especificado',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Conflictos de horario obtenidos exitosamente',
                'success' => true,
                'data' => $conflictos
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los conflictos de horario',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSchedulesByRange(Request $request): JsonResponse {
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
            $request->validate([
                'hora_inicio' => 'required|date_format:H:i',
                'hora_fin' => 'required|date_format:H:i'
            ]);

            $horarios = Cache::remember('horarios_rango_' . $request->hora_inicio . '_' . $request->hora_fin, 60, function () use ($request) {
                return (new horarios())->getHorariosByRango($request->hora_inicio, $request->hora_fin);
            });

            if (!$horarios || $horarios->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron horarios en el rango especificado',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Horarios obtenidos exitosamente',
                'success' => true,
                'data' => $horarios
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los horarios por rango',
                'success' => false,
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
}
