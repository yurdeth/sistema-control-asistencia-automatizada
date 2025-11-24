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
use Illuminate\Support\Facades\Log;

class HorariosController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {

            $withRelations = $request->query('with_relations', 'false') === 'true';
            
            $query = horarios::query();
            
            if ($withRelations) {
                $query->with([
                    'grupo:id,numero_grupo,materia_id,docente_id',
                    'grupo.materia:id,nombre,codigo',
                    'grupo.docente:id,nombre_completo',
                    'aula:id,nombre,codigo,ubicacion,capacidad_pupitres'
                ]);
            } else {
  
                $query->with(['grupo:id,numero_grupo', 'aula:id,nombre']);
            }
            
            $horarios = $query
                ->select('id', 'grupo_id', 'aula_id', 'dia_semana', 'hora_inicio', 'hora_fin')
                ->orderBy('dia_semana')
                ->orderBy('hora_inicio')
                ->get();

            if ($horarios->isEmpty()) {
                return response()->json([
                    'message' => 'No hay horarios disponibles',
                    'success' => true,
                    'data' => []
                ], 200);
            }

            $horariosData = $horarios->map(function ($horario) use ($withRelations) {
                $data = [
                    'id' => $horario->id,
                    'grupo_id' => $horario->grupo_id,
                    'aula_id' => $horario->aula_id,
                    'dia_semana' => $horario->dia_semana,
                    'hora_inicio' => substr($horario->hora_inicio ?? '', 0, 5),
                    'hora_fin' => substr($horario->hora_fin ?? '', 0, 5),
                ];

                if ($withRelations && $horario->grupo) {
                    $data['grupo'] = [
                        'id' => $horario->grupo->id,
                        'numero_grupo' => $horario->grupo->numero_grupo,
                        'materia_id' => $horario->grupo->materia_id,
                        'docente_id' => $horario->grupo->docente_id,
                        'materia' => $horario->grupo->materia ? [
                            'id' => $horario->grupo->materia->id,
                            'nombre' => $horario->grupo->materia->nombre,
                            'codigo' => $horario->grupo->materia->codigo ?? null,
                        ] : null,
                        'docente' => $horario->grupo->docente ? [
                            'id' => $horario->grupo->docente->id,
                            'nombre_completo' => $horario->grupo->docente->nombre_completo,
                        ] : null,
                    ];
                } else {
                    $data['numero_grupo'] = $horario->grupo->numero_grupo ?? null;
                }

                if ($withRelations && $horario->aula) {
                    $data['aula'] = [
                        'id' => $horario->aula->id,
                        'nombre' => $horario->aula->nombre,
                        'codigo' => $horario->aula->codigo ?? null,
                        'ubicacion' => $horario->aula->ubicacion ?? null,
                        'capacidad_pupitres' => $horario->aula->capacidad_pupitres ?? 0,
                    ];
                } else {
                    $data['aula_nombre'] = $horario->aula->nombre ?? null;
                }

                return $data;
            });

            return response()->json([
                'message' => 'Horarios obtenidos exitosamente',
                'success' => true,
                'data' => $horariosData
            ], 200);

        } catch (Exception $e) {
            Log::error('Error en HorariosController@index', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'message' => 'Error al obtener los horarios',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                'success' => false
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
            $horario = horarios::with(['grupo.materia', 'grupo.docente', 'grupo.ciclo', 'aula'])
                ->find($id);

            if (!$horario) {
                return response()->json([
                    'message' => 'Horario no encontrado',
                    'success' => false
                ], 404);
            }
            $horarioData = [
                'id' => $horario->id,
                'grupo_id' => $horario->grupo_id,
                'aula_id' => $horario->aula_id,
                'dia_semana' => $horario->dia_semana,
                'hora_inicio' => $horario->hora_inicio,
                'hora_fin' => $horario->hora_fin,
                'aula' => $horario->aula ? [
                    'id' => $horario->aula->id,
                    'nombre' => $horario->aula->nombre ?? $horario->aula->codigo,
                    'codigo' => $horario->aula->codigo,
                    'capacidad' => $horario->aula->capacidad_pupitres,
                    'ubicacion' => $horario->aula->ubicacion,
                ] : null,
                'grupo' => $horario->grupo ? [
                    'id' => $horario->grupo->id,
                    'numero_grupo' => $horario->grupo->numero_grupo,
                    'materia_nombre' => $horario->grupo->materia->nombre ?? null,
                    'docente_nombre' => $horario->grupo->docente->nombre_completo ?? null,
                    'ciclo_nombre' => $horario->grupo->ciclo->nombre ?? null,
                ] : null,
            ];

            return response()->json([
                'message' => 'Horario obtenido exitosamente',
                'success' => true,
                'data' => $horarioData
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
            RolesEnum::ROOT->value,
            RolesEnum::ADMINISTRADOR_ACADEMICO->value,
            RolesEnum::JEFE_DEPARTAMENTO->value,
            RolesEnum::COORDINADOR_CARRERAS->value,
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

            $grupo = grupos::find($request->grupo_id);
            
            if (!$grupo) {
                return response()->json([
                    'message' => 'Grupo no encontrado',
                    'success' => false
                ], 404);
            }

            // Validación: Límite de 2 días por semana por docente-materia
            $dias_asignados_query = horarios::join('grupos', 'horarios.grupo_id', '=', 'grupos.id')
                ->where('grupos.docente_id', $grupo->docente_id)
                ->where('grupos.materia_id', $grupo->materia_id)
                ->distinct()
                ->pluck('horarios.dia_semana');

            $dias_asignados = $dias_asignados_query->count();
            $dia_ya_asignado = $dias_asignados_query->contains($request->dia_semana);

            if (!$dia_ya_asignado && $dias_asignados >= 2) {
                return response()->json([
                    'message' => 'Límite de días alcanzado: El docente ya tiene asignados 2 días para esta materia (máximo permitido: 2 días por semana)',
                    'success' => false,
                    'dias_actuales' => $dias_asignados,
                    'dias_ocupados' => $dias_asignados_query->toArray()
                ], 409);
            }

            // Validar conflicto de aula
            $conflictoAula = horarios::where('aula_id', $request->aula_id)
                ->where('dia_semana', $request->dia_semana)
                ->where(function ($query) use ($request) {
                    $query->where('hora_inicio', '<', $request->hora_fin)
                          ->where('hora_fin', '>', $request->hora_inicio);
                })
                ->exists();

            if ($conflictoAula) {
                return response()->json([
                    'message' => 'Conflicto de horario: El aula ya está ocupada en este horario',
                    'success' => false
                ], 409);
            }

            // Validar conflicto de grupo
            $conflictoGrupo = horarios::where('grupo_id', $request->grupo_id)
                ->where('dia_semana', $request->dia_semana)
                ->where(function ($query) use ($request) {
                    $query->where('hora_inicio', '<', $request->hora_fin)
                          ->where('hora_fin', '>', $request->hora_inicio);
                })
                ->exists();

            if ($conflictoGrupo) {
                return response()->json([
                    'message' => 'Conflicto de horario: El grupo ya tiene clase en este horario',
                    'success' => false
                ], 409);
            }

            // Validaciones extras
            $conflictoDocente = horarios::join('grupos', 'horarios.grupo_id', '=', 'grupos.id')
                ->where('grupos.docente_id', $grupo->docente_id)
                ->where('horarios.dia_semana', $request->dia_semana)
                ->where(function ($query) use ($request) {
                    $query->where('horarios.hora_inicio', '<', $request->hora_fin)
                          ->where('horarios.hora_fin', '>', $request->hora_inicio);
                })
                ->exists();

            if ($conflictoDocente) {
                return response()->json([
                    'message' => 'Conflicto de horario: El docente ya tiene otra clase asignada en este horario',
                    'success' => false
                ], 409);
            }

            DB::beginTransaction();

            $horario = horarios::create($validation);

            DB::commit();
            
            Cache::forget('horarios_index_all');
            Cache::forget('horarios_index');
            Cache::forget('horarios_grupo_' . $request->grupo_id);

            $horario->load(['grupo.materia', 'grupo.docente', 'grupo.ciclo', 'aula']);
            
            $horarioData = [
                'id' => $horario->id,
                'grupo_id' => $horario->grupo_id,
                'aula_id' => $horario->aula_id,
                'dia_semana' => $horario->dia_semana,
                'hora_inicio' => $horario->hora_inicio,
                'hora_fin' => $horario->hora_fin,
                'aula' => $horario->aula ? [
                    'id' => $horario->aula->id,
                    'nombre' => $horario->aula->nombre ?? $horario->aula->codigo,
                    'codigo' => $horario->aula->codigo,
                    'capacidad' => $horario->aula->capacidad_pupitres,
                    'ubicacion' => $horario->aula->ubicacion,
                ] : null,
            ];

            return response()->json([
                'message' => 'Horario creado exitosamente',
                'success' => true,
                'data' => $horarioData
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'success' => false,
                'errors' => $e->errors()
            ], 422);
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
            RolesEnum::ROOT->value,
            RolesEnum::ADMINISTRADOR_ACADEMICO->value,
            RolesEnum::JEFE_DEPARTAMENTO->value,
            RolesEnum::COORDINADOR_CARRERAS->value,
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        $request->merge([
            'grupo_id' => $this->sanitizeInput($request->grupo_id ?? ''),
            'aula_id' => $this->sanitizeInput($request->aula_id ?? ''),
            'dia_semana' => $this->sanitizeInput($request->dia_semana ?? ''),
            'hora_inicio' => $this->sanitizeInput($request->hora_inicio ?? ''),
            'hora_fin' => $this->sanitizeInput($request->hora_fin ?? '')
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

            $aula_id = $validation['aula_id'] ?? $horario->aula_id;
            $grupo_id = $validation['grupo_id'] ?? $horario->grupo_id;
            $dia_semana = $validation['dia_semana'] ?? $horario->dia_semana;
            $hora_inicio = $validation['hora_inicio'] ?? $horario->hora_inicio;
            $hora_fin = $validation['hora_fin'] ?? $horario->hora_fin;

            $grupo = grupos::find($grupo_id);
            
            if (!$grupo) {
                return response()->json([
                    'message' => 'Grupo no encontrado',
                    'success' => false
                ], 404);
            }
//aca valido que para asignar aulas sea dos dias nada mas por materia
            if (isset($validation['dia_semana']) && $validation['dia_semana'] !== $horario->dia_semana) {
                
                $dias_asignados_query = horarios::join('grupos', 'horarios.grupo_id', '=', 'grupos.id')
                    ->where('grupos.docente_id', $grupo->docente_id)
                    ->where('grupos.materia_id', $grupo->materia_id)
                    ->where('horarios.id', '!=', $id)
                    ->distinct()
                    ->pluck('horarios.dia_semana');

                $dias_asignados = $dias_asignados_query->count();
                $dia_ya_asignado = $dias_asignados_query->contains($validation['dia_semana']);

                if (!$dia_ya_asignado && $dias_asignados >= 2) {
                    return response()->json([
                        'message' => 'Límite de días alcanzado: El docente ya tiene asignados 2 días para esta materia (máximo permitido: 2 días por semana)',
                        'success' => false,
                        'dias_actuales' => $dias_asignados,
                        'dias_ocupados' => $dias_asignados_query->toArray()
                    ], 409);
                }
            }


            $conflictoAula = horarios::where('aula_id', $aula_id)
                ->where('dia_semana', $dia_semana)
                ->where('id', '!=', $id)
                ->where(function ($query) use ($hora_inicio, $hora_fin) {
                    $query->where('hora_inicio', '<', $hora_fin)
                          ->where('hora_fin', '>', $hora_inicio);
                })
                ->exists();

            if ($conflictoAula) {
                return response()->json([
                    'message' => 'Conflicto de horario: El aula ya está ocupada en este horario',
                    'success' => false
                ], 409);
            }

            $conflictoGrupo = horarios::where('grupo_id', $grupo_id)
                ->where('dia_semana', $dia_semana)
                ->where('id', '!=', $id)
                ->where(function ($query) use ($hora_inicio, $hora_fin) {
                    $query->where('hora_inicio', '<', $hora_fin)
                          ->where('hora_fin', '>', $hora_inicio);
                })
                ->exists();

            if ($conflictoGrupo) {
                return response()->json([
                    'message' => 'Conflicto de horario: El grupo ya tiene clase en este horario',
                    'success' => false
                ], 409);
            }

            $conflictoDocente = horarios::join('grupos', 'horarios.grupo_id', '=', 'grupos.id')
                ->where('grupos.docente_id', $grupo->docente_id)
                ->where('horarios.dia_semana', $dia_semana)
                ->where('horarios.id', '!=', $id)
                ->where(function ($query) use ($hora_inicio, $hora_fin) {
                    $query->where('horarios.hora_inicio', '<', $hora_fin)
                          ->where('horarios.hora_fin', '>', $hora_inicio);
                })
                ->exists();

            if ($conflictoDocente) {
                return response()->json([
                    'message' => 'Conflicto de horario: El docente ya tiene otra clase asignada en este horario',
                    'success' => false
                ], 409);
            }

            DB::beginTransaction();
            $horario->update($validation);
            DB::commit();
            
            Cache::forget('horarios_index_all');
            Cache::forget('horarios_index');
            Cache::forget('horarios_grupo_' . $grupo_id);

            $horario->load(['grupo.materia', 'grupo.docente', 'grupo.ciclo', 'aula']);
            
            $horarioData = [
                'id' => $horario->id,
                'grupo_id' => $horario->grupo_id,
                'aula_id' => $horario->aula_id,
                'dia_semana' => $horario->dia_semana,
                'hora_inicio' => $horario->hora_inicio,
                'hora_fin' => $horario->hora_fin,
                'aula' => $horario->aula ? [
                    'id' => $horario->aula->id,
                    'nombre' => $horario->aula->nombre ?? $horario->aula->codigo,
                    'codigo' => $horario->aula->codigo,
                    'capacidad' => $horario->aula->capacidad_pupitres,
                    'ubicacion' => $horario->aula->ubicacion,
                ] : null,
            ];

            return response()->json([
                'message' => 'Horario actualizado exitosamente',
                'success' => true,
                'data' => $horarioData
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar el horario',
                'success' => false,
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
            $horario = horarios::where('id', $id)->lockForUpdate()->first();

            if (!$horario) {
                return response()->json([
                    'message' => 'Horario no encontrado',
                    'success' => false
                ], 404);
            }

            $grupo_id = $horario->grupo_id;
            $aula_id = $horario->aula_id;

            DB::beginTransaction();
            $horario->delete();
            DB::commit();
            
            Cache::forget('horarios_index_all');
            Cache::forget('horarios_index');
            Cache::forget('horarios_grupo_' . $grupo_id);
            Cache::forget('horarios_aula_' . $aula_id);

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
                return horarios::with(['aula', 'grupo.materia', 'grupo.docente', 'grupo.ciclo'])
                    ->where('grupo_id', $grupo_id)
                    ->get();
            });

            if ($horarios->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron horarios para el grupo especificado',
                    'success' => false
                ], 200);
            }

            $horarios = $horarios->map(function ($horario) {
                return [
                    'id' => $horario->id,
                    'grupo_id' => $horario->grupo_id,
                    'aula_id' => $horario->aula_id,
                    'dia_semana' => $horario->dia_semana,
                    'hora_inicio' => $horario->hora_inicio,
                    'hora_fin' => $horario->hora_fin,
                    'aula' => $horario->aula ? [
                        'id' => $horario->aula->id,
                        'nombre' => $horario->aula->nombre ?? $horario->aula->codigo,
                        'codigo' => $horario->aula->codigo,
                        'capacidad' => $horario->aula->capacidad_pupitres,
                        'ubicacion' => $horario->aula->ubicacion,
                        'tipo' => $horario->aula->tipo ?? 'Estándar',
                    ] : null,
                ];
            });

            return response()->json([
                'message' => 'Horarios obtenidos exitosamente',
                'data' => $horarios,
                'success' => true
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
                return horarios::with(['aula', 'grupo.materia', 'grupo.docente', 'grupo.ciclo'])
                    ->where('aula_id', $aula_id)
                    ->get();
            });

            if ($horarios->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron horarios para el aula especificada',
                    'success' => false
                ], 404);
            }

            $horarios = $horarios->map(function ($horario) {
                return [
                    'id' => $horario->id,
                    'grupo_id' => $horario->grupo_id,
                    'aula_id' => $horario->aula_id,
                    'dia_semana' => $horario->dia_semana,
                    'hora_inicio' => $horario->hora_inicio,
                    'hora_fin' => $horario->hora_fin,
                    'grupo' => $horario->grupo ? [
                        'id' => $horario->grupo->id,
                        'numero_grupo' => $horario->grupo->numero_grupo,
                        'materia_nombre' => $horario->grupo->materia->nombre ?? null,
                    ] : null,
                ];
            });

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
                return horarios::with(['aula', 'grupo.materia', 'grupo.docente', 'grupo.ciclo'])
                    ->where('dia_semana', $dia_semana)
                    ->get();
            });

            if ($horarios->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron horarios para el día especificado',
                    'success' => false
                ], 404);
            }

            $horarios = $horarios->map(function ($horario) {
                return [
                    'id' => $horario->id,
                    'grupo_id' => $horario->grupo_id,
                    'aula_id' => $horario->aula_id,
                    'dia_semana' => $horario->dia_semana,
                    'hora_inicio' => $horario->hora_inicio,
                    'hora_fin' => $horario->hora_fin,
                    'aula' => $horario->aula ? [
                        'nombre' => $horario->aula->nombre ?? $horario->aula->codigo,
                    ] : null,
                    'grupo' => $horario->grupo ? [
                        'numero_grupo' => $horario->grupo->numero_grupo,
                        'materia_nombre' => $horario->grupo->materia->nombre ?? null,
                    ] : null,
                ];
            });

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
                return horarios::with(['aula', 'grupo.materia', 'grupo.docente'])
                    ->where('hora_inicio', '>=', $request->hora_inicio)
                    ->where('hora_fin', '<=', $request->hora_fin)
                    ->get();
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