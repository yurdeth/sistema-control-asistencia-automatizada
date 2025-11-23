<?php

namespace App\Http\Controllers;

use App\Models\grupos;
use App\RolesEnum;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GruposController extends Controller {

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
        $grupos = grupos::with(['materia', 'docente', 'ciclo'])->get();

        if ($grupos->isEmpty()) {
            return response()->json([
                'message' => 'No hay grupos disponibles',
                'success' => true,
                'data' => []
            ], 200);
        }

        $grupos = $grupos->map(function ($grupo) {
            return [
                'id' => $grupo->id,
                'numero_grupo' => $grupo->numero_grupo,
                'capacidad_maxima' => $grupo->capacidad_maxima,
                'estudiantes_inscrito' => $grupo->estudiantes_inscrito,
                'estado' => $grupo->estado,
                'materia_id' => $grupo->materia_id,
                'materia_nombre' => $grupo->materia->nombre ?? null,
                'docente_id' => $grupo->docente_id,
                'docente_nombre' => $grupo->docente->nombre_completo ?? null,
                'ciclo_id' => $grupo->ciclo_id,
                'ciclo_nombre' => $grupo->ciclo->nombre ?? null,
            ];
        });

        return response()->json([
            'message' => 'Grupos obtenidos exitosamente',
            'success' => true,
            'data' => $grupos
        ], 200);
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

        $grupo = grupos::with(['materia', 'docente', 'ciclo'])->find($id);

        if (!$grupo) {
            return response()->json([
                'message' => 'Grupo no encontrado',
                'success' => false
            ], 404);
        }

        $grupoData = [
            'id' => $grupo->id,
            'numero_grupo' => $grupo->numero_grupo,
            'capacidad_maxima' => $grupo->capacidad_maxima,
            'estudiantes_inscrito' => $grupo->estudiantes_inscrito,
            'estado' => $grupo->estado,
            'materia_id' => $grupo->materia_id,
            'materia_nombre' => $grupo->materia->nombre ?? null,
            'docente_id' => $grupo->docente_id,
            'docente_nombre' => $grupo->docente->nombre_completo ?? null,
            'ciclo_id' => $grupo->ciclo_id,
            'ciclo_nombre' => $grupo->ciclo->nombre ?? null,
        ];

        return response()->json([
            'message' => 'Grupo obtenido exitosamente',
            'success' => true,
            'data' => $grupoData
        ], 200);
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

        $rules = [
            'materia_id' => 'required|exists:materias,id',
            'ciclo_id' => 'required|exists:ciclos_academicos,id',
            'docente_id' => 'required|exists:users,id',
            'numero_grupo' => 'required|string|max:10',
            'capacidad_maxima' => 'required|integer|min:1',
            'estudiantes_inscrito' => 'nullable|integer|min:0',
            'estado' => 'required|in:activo,finalizado,cancelado'
        ];

        $messages = [
            'materia_id.required' => 'El campo materia_id es obligatorio.',
            'materia_id.exists' => 'La materia especificada no existe.',
            'ciclo_id.required' => 'El campo ciclo_id es obligatorio.',
            'ciclo_id.exists' => 'El ciclo académico especificado no existe.',
            'docente_id.required' => 'El campo docente_id es obligatorio.',
            'docente_id.exists' => 'El docente especificado no existe.',
            'numero_grupo.required' => 'El campo numero_grupo es obligatorio.',
            'numero_grupo.string' => 'El campo numero_grupo debe ser una cadena de texto.',
            'numero_grupo.max' => 'El campo numero_grupo no debe exceder los 10 caracteres.',
            'capacidad_maxima.required' => 'El campo capacidad_maxima es obligatorio.',
            'capacidad_maxima.integer' => 'El campo capacidad_maxima debe ser un número entero.',
            'capacidad_maxima.min' => 'El campo capacidad_maxima debe ser al menos 1.',
            'estudiantes_inscrito.integer' => 'El campo estudiantes_inscrito debe ser un número entero.',
            'estudiantes_inscrito.min' => 'El campo estudiantes_inscrito debe ser al menos 0.',
            'estado.required' => 'El campo estado es obligatorio.',
            'estado.in' => 'El campo estado debe ser uno de los siguientes valores: activo, finalizado, cancelado.'
        ];

        try {
            $validator = Validator::make($request->all(), $rules, $messages);
            
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                    'success' => false
                ], 422);
            }

            $validatedData = $validator->validated();

            // Verificar docente
            $docente_id_existe = DB::table('usuario_roles')
                ->where('usuario_id', $validatedData['docente_id'])
                ->where('rol_id', 5)
                ->value('usuario_id');

            if (!$docente_id_existe) {
                return response()->json([
                    'message' => 'Error: este docente no existe o no tiene el rol adecuado.',
                    'success' => false
                ], 422);
            }

            // Verificar duplicados
            $grupo_existente = grupos::where('materia_id', $validatedData['materia_id'])
                ->where('ciclo_id', $validatedData['ciclo_id'])
                ->where('numero_grupo', $validatedData['numero_grupo'])
                ->first();

            if ($grupo_existente) {
                return response()->json([
                    'message' => 'Error: ya existe un grupo con este número para la misma materia y ciclo.',
                    'success' => false
                ], 422);
            }

            // 🔥 FIX: Establecer valor por defecto para estudiantes_inscrito
            if (!isset($validatedData['estudiantes_inscrito']) || $validatedData['estudiantes_inscrito'] === null) {
                $validatedData['estudiantes_inscrito'] = 0;
            }

            DB::beginTransaction();

            $grupo = grupos::create($validatedData);

            DB::commit();
            
            $grupo->load(['materia', 'docente', 'ciclo']);
            
            $grupoData = [
                'id' => $grupo->id,
                'numero_grupo' => $grupo->numero_grupo,
                'capacidad_maxima' => $grupo->capacidad_maxima,
                'estudiantes_inscrito' => $grupo->estudiantes_inscrito,
                'estado' => $grupo->estado,
                'materia_id' => $grupo->materia_id,
                'materia_nombre' => $grupo->materia->nombre ?? null,
                'docente_id' => $grupo->docente_id,
                'docente_nombre' => $grupo->docente->nombre_completo ?? null,
                'ciclo_id' => $grupo->ciclo_id,
                'ciclo_nombre' => $grupo->ciclo->nombre ?? null,
            ];

            return response()->json([
                'message' => 'Grupo creado exitosamente',
                'success' => true,
                'data' => $grupoData
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear el grupo',
                'error' => $e->getMessage(),
                'success' => false
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
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        $grupo = grupos::find($id);

        if (!$grupo) {
            return response()->json([
                'message' => 'Grupo no encontrado',
                'success' => false
            ], 404);
        }

        $rules = [
            'materia_id' => 'required|exists:materias,id',
            'ciclo_id' => 'required|exists:ciclos_academicos,id',
            'docente_id' => 'required|exists:users,id',
            'numero_grupo' => 'required|string|max:10',
            'capacidad_maxima' => 'required|integer|min:1',
            'estudiantes_inscrito' => 'nullable|integer|min:0',
            'estado' => 'required|in:activo,finalizado,cancelado'
        ];

        $messages = [
            'materia_id.required' => 'El campo materia_id es obligatorio.',
            'materia_id.exists' => 'La materia especificada no existe.',
            'ciclo_id.required' => 'El campo ciclo_id es obligatorio.',
            'ciclo_id.exists' => 'El ciclo académico especificado no existe.',
            'docente_id.required' => 'El campo docente_id es obligatorio.',
            'docente_id.exists' => 'El docente especificado no existe.',
            'numero_grupo.required' => 'El campo numero_grupo es obligatorio.',
            'numero_grupo.string' => 'El campo numero_grupo debe ser una cadena de texto.',
            'numero_grupo.max' => 'El campo numero_grupo no debe exceder los 10 caracteres.',
            'capacidad_maxima.required' => 'El campo capacidad_maxima es obligatorio.',
            'capacidad_maxima.integer' => 'El campo capacidad_maxima debe ser un número entero.',
            'capacidad_maxima.min' => 'El campo capacidad_maxima debe ser al menos 1.',
            'estudiantes_inscrito.integer' => 'El campo estudiantes_inscrito debe ser un número entero.',
            'estudiantes_inscrito.min' => 'El campo estudiantes_inscrito debe ser al menos 0.',
            'estado.required' => 'El campo estado es obligatorio.',
            'estado.in' => 'El campo estado debe ser uno de los siguientes valores: activo, finalizado, cancelado.'
        ];

        try {
            $validator = Validator::make($request->all(), $rules, $messages);
            
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                    'success' => false
                ], 422);
            }

            $validatedData = $validator->validated();

            // Verificar docente
            $docente_id_existe = DB::table('usuario_roles')
                ->where('usuario_id', $validatedData['docente_id'])
                ->where('rol_id', 5)
                ->value('usuario_id');

            if (!$docente_id_existe) {
                return response()->json([
                    'message' => 'Error: este docente no existe o no tiene el rol adecuado.',
                    'success' => false
                ], 422);
            }

            // 🔥 FIX: Establecer valor por defecto para estudiantes_inscrito
            if (!isset($validatedData['estudiantes_inscrito']) || $validatedData['estudiantes_inscrito'] === null) {
                $validatedData['estudiantes_inscrito'] = 0;
            }

            DB::beginTransaction();

            $grupo->update($validatedData);

            DB::commit();

            $grupo->load(['materia', 'docente', 'ciclo']);
            
            $grupoData = [
                'id' => $grupo->id,
                'numero_grupo' => $grupo->numero_grupo,
                'capacidad_maxima' => $grupo->capacidad_maxima,
                'estudiantes_inscrito' => $grupo->estudiantes_inscrito,
                'estado' => $grupo->estado,
                'materia_id' => $grupo->materia_id,
                'materia_nombre' => $grupo->materia->nombre ?? null,
                'docente_id' => $grupo->docente_id,
                'docente_nombre' => $grupo->docente->nombre_completo ?? null,
                'ciclo_id' => $grupo->ciclo_id,
                'ciclo_nombre' => $grupo->ciclo->nombre ?? null,
            ];

            return response()->json([
                'message' => 'Grupo actualizado exitosamente',
                'success' => true,
                'data' => $grupoData
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar el grupo',
                'error' => $e->getMessage(),
                'success' => false
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
            $grupo = grupos::where('id', $id)->lockForUpdate()->first();

            if (!$grupo) {
                return response()->json([
                    'message' => 'Grupo no encontrado',
                    'success' => false
                ], 404);
            }

            $grupo->delete();

            return response()->json([
                'message' => 'Grupo eliminado exitosamente',
                'success' => true
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el grupo',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function getGroupsBySubject($id): JsonResponse {
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
            $grupos = grupos::with(['materia', 'docente', 'ciclo'])
                ->where('materia_id', $id)
                ->get();

            if ($grupos->isEmpty()) {
                return response()->json([
                    'message' => 'No hay grupos disponibles para esta materia',
                    'success' => false
                ], 404);
            }

            $grupos = $grupos->map(function ($grupo) {
                return [
                    'id' => $grupo->id,
                    'numero_grupo' => $grupo->numero_grupo,
                    'capacidad_maxima' => $grupo->capacidad_maxima,
                    'estudiantes_inscrito' => $grupo->estudiantes_inscrito,
                    'estado' => $grupo->estado,
                    'materia_id' => $grupo->materia_id,
                    'materia_nombre' => $grupo->materia->nombre ?? null,
                    'docente_id' => $grupo->docente_id,
                    'docente_nombre' => $grupo->docente->nombre_completo ?? null,
                    'ciclo_id' => $grupo->ciclo_id,
                    'ciclo_nombre' => $grupo->ciclo->nombre ?? null,
                ];
            });

            return response()->json([
                'message' => 'Grupos obtenidos exitosamente',
                'success' => true,
                'data' => $grupos
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los grupos',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function getGroupsByCycle($id): JsonResponse {
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
            $grupos = grupos::with(['materia', 'docente', 'ciclo'])
                ->where('ciclo_id', $id)
                ->get();

            if ($grupos->isEmpty()) {
                return response()->json([
                    'message' => 'No hay grupos disponibles para este ciclo',
                    'success' => true
                ], 404);
            }

            $grupos = $grupos->map(function ($grupo) {
                return [
                    'id' => $grupo->id,
                    'numero_grupo' => $grupo->numero_grupo,
                    'capacidad_maxima' => $grupo->capacidad_maxima,
                    'estudiantes_inscrito' => $grupo->estudiantes_inscrito,
                    'estado' => $grupo->estado,
                    'materia_id' => $grupo->materia_id,
                    'materia_nombre' => $grupo->materia->nombre ?? null,
                    'docente_id' => $grupo->docente_id,
                    'docente_nombre' => $grupo->docente->nombre_completo ?? null,
                    'ciclo_id' => $grupo->ciclo_id,
                    'ciclo_nombre' => $grupo->ciclo->nombre ?? null,
                ];
            });

            return response()->json([
                'message' => 'Grupos obtenidos exitosamente',
                'success' => true,
                'data' => $grupos
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los grupos',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function getGroupsByProfessor($id): JsonResponse {
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
            $grupos = grupos::with(['materia', 'docente', 'ciclo'])
                ->where('docente_id', $id)
                ->get();

            if ($grupos->isEmpty()) {
                return response()->json([
                    'message' => 'No hay grupos disponibles para este ciclo',
                    'success' => true
                ], 404);
            }
            $grupos = $grupos->map(function ($grupo) {
                return [
                    'id' => $grupo->id,
                    'numero_grupo' => $grupo->numero_grupo,
                    'capacidad_maxima' => $grupo->capacidad_maxima,
                    'estudiantes_inscrito' => $grupo->estudiantes_inscrito,
                    'estado' => $grupo->estado,
                    'materia_id' => $grupo->materia_id,
                    'materia_nombre' => $grupo->materia->nombre ?? null,
                    'docente_id' => $grupo->docente_id,
                    'docente_nombre' => $grupo->docente->nombre_completo ?? null,
                    'ciclo_id' => $grupo->ciclo_id,
                    'ciclo_nombre' => $grupo->ciclo->nombre ?? null,
                ];
            });

            return response()->json([
                'message' => 'Grupos obtenidos exitosamente',
                'success' => true,
                'data' => $grupos
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los grupos',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function getGroupsByStatus(string $estado): JsonResponse {
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
            $estado = $this->sanitizeInput($estado);
            
            $grupos = grupos::with(['materia', 'docente', 'ciclo'])
                ->where('estado', $estado)
                ->get();

            if ($grupos->isEmpty()) {
                return response()->json([
                    'message' => 'No hay grupos disponibles para este ciclo',
                    'success' => true
                ], 404);
            }


            $grupos = $grupos->map(function ($grupo) {
                return [
                    'id' => $grupo->id,
                    'numero_grupo' => $grupo->numero_grupo,
                    'capacidad_maxima' => $grupo->capacidad_maxima,
                    'estudiantes_inscrito' => $grupo->estudiantes_inscrito,
                    'estado' => $grupo->estado,
                    'materia_id' => $grupo->materia_id,
                    'materia_nombre' => $grupo->materia->nombre ?? null,
                    'docente_id' => $grupo->docente_id,
                    'docente_nombre' => $grupo->docente->nombre_completo ?? null,
                    'ciclo_id' => $grupo->ciclo_id,
                    'ciclo_nombre' => $grupo->ciclo->nombre ?? null,
                ];
            });

            return response()->json([
                'message' => 'Grupos obtenidos exitosamente',
                'success' => true,
                'data' => $grupos
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los grupos',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function getAvailableGroups(): JsonResponse {
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

            $grupos = grupos::with(['materia', 'docente', 'ciclo'])
                ->where('estado', 'activo')
                ->get();

            if ($grupos->isEmpty()) {
                return response()->json([
                    'message' => 'No hay grupos disponibles para este ciclo',
                    'success' => true
                ], 404);
            }

            $grupos = $grupos->map(function ($grupo) {
                return [
                    'id' => $grupo->id,
                    'numero_grupo' => $grupo->numero_grupo,
                    'capacidad_maxima' => $grupo->capacidad_maxima,
                    'estudiantes_inscrito' => $grupo->estudiantes_inscrito,
                    'estado' => $grupo->estado,
                    'materia_id' => $grupo->materia_id,
                    'materia_nombre' => $grupo->materia->nombre ?? null,
                    'docente_id' => $grupo->docente_id,
                    'docente_nombre' => $grupo->docente->nombre_completo ?? null,
                    'ciclo_id' => $grupo->ciclo_id,
                    'ciclo_nombre' => $grupo->ciclo->nombre ?? null,
                ];
            });

            return response()->json([
                'message' => 'Grupos obtenidos exitosamente',
                'success' => true,
                'data' => $grupos
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los grupos',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function getGroupProfessor(Request $request): JsonResponse {
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

        $rules = [
            'grupo_id' => 'required|exists:grupos,id',
            'materia_id' => 'required|exists:materias,id',
        ];

        $messages = [
            'grupo_id.required' => 'El campo grupo_id es obligatorio.',
            'grupo_id.exists' => 'El grupo especificado no existe.',
            'materia_id.required' => 'El campo materia_id es obligatorio.',
            'materia_id.exists' => 'La materia especificada no existe.',
        ];

        try {
            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                    'success' => false
                ], 422);
            }

            $grupo_id = $this->sanitizeInput($request->grupo_id);
            $materia_id = $this->sanitizeInput($request->materia_id);
            
            $grupo = grupos::with('docente')
                ->where('id', $grupo_id)
                ->where('materia_id', $materia_id)
                ->first();

            if (!$grupo) {
                return response()->json([
                    'message' => 'No se encontró el profesor para este grupo',
                    'success' => true
                ], 404);
            }

            return response()->json([
                'message' => 'Profesor obtenido exitosamente',
                'success' => true,
                'data' => $grupo->docente
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el profesor',
                'error' => $e->getMessage(),
                'success' => false
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

    public function asignarAula(Request $request, $grupo_id): JsonResponse {
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
            'dia_semana' => $this->sanitizeInput($request->dia_semana),
            'hora_inicio' => $this->sanitizeInput($request->hora_inicio),
            'hora_fin' => $this->sanitizeInput($request->hora_fin),
        ]);

        $rules = [
            'dia_semana' => 'required|in:Lunes,Martes,Miercoles,Jueves,Viernes,Sabado,Domingo',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
        ];

        $messages = [
            'dia_semana.required' => 'El día de la semana es obligatorio.',
            'dia_semana.in' => 'Día inválido.',
            'hora_inicio.required' => 'La hora de inicio es obligatoria.',
            'hora_inicio.date_format' => 'Formato de hora inicio inválido (HH:MM).',
            'hora_fin.required' => 'La hora fin es obligatoria.',
            'hora_fin.date_format' => 'Formato de hora fin inválido (HH:MM).',
            'hora_fin.after' => 'La hora fin debe ser posterior a la hora inicio.',
        ];

        try {
            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                    'success' => false
                ], 422);
            }

            $grupo = DB::table('grupos')->where('id', $grupo_id)->first();
            if (!$grupo) {
                return response()->json([
                    'message' => 'Grupo no encontrado',
                    'success' => false
                ], 404);
            }

            $capacidad_grupo = $grupo->capacidad_maxima;

            $aulas_disponibles = DB::table('aulas')
                ->where('capacidad_pupitres', '>=', $capacidad_grupo)
                ->where('estado', 'disponible')
                ->whereNotExists(function ($query) use ($request) {
                    $query->select(DB::raw(1))
                        ->from('horarios')
                        ->whereColumn('horarios.aula_id', 'aulas.id')
                        ->where('horarios.dia_semana', $request->dia_semana)
                        ->where(function ($q) use ($request) {
                            $q->whereBetween('horarios.hora_inicio', [$request->hora_inicio, $request->hora_fin])
                              ->orWhereBetween('horarios.hora_fin', [$request->hora_inicio, $request->hora_fin])
                              ->orWhere(function ($q2) use ($request) {
                                  $q2->where('horarios.hora_inicio', '<=', $request->hora_inicio)
                                     ->where('horarios.hora_fin', '>=', $request->hora_fin);
                              });
                        });
                })
                ->orderBy('capacidad_pupitres', 'asc')
                ->get();

            if ($aulas_disponibles->isEmpty()) {
                return response()->json([
                    'message' => 'No hay aulas disponibles con la capacidad necesaria en ese horario',
                    'success' => false,
                    'capacidad_requerida' => $capacidad_grupo
                ], 404);
            }

            $aula_asignada = $aulas_disponibles->first();

            DB::beginTransaction();

            $horario_id = DB::table('horarios')->insertGetId([
                'grupo_id' => $grupo_id,
                'aula_id' => $aula_asignada->id,
                'dia_semana' => $request->dia_semana,
                'hora_inicio' => $request->hora_inicio,
                'hora_fin' => $request->hora_fin,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Aula asignada exitosamente',
                'success' => true,
                'data' => [
                    'horario_id' => $horario_id,
                    'aula' => [
                        'id' => $aula_asignada->id,
                        'codigo' => $aula_asignada->codigo,
                        'nombre' => $aula_asignada->nombre ?? $aula_asignada->codigo,
                        'capacidad' => $aula_asignada->capacidad_pupitres,
                        'ubicacion' => $aula_asignada->ubicacion
                    ],
                    'horario' => [
                        'dia' => $request->dia_semana,
                        'hora_inicio' => $request->hora_inicio,
                        'hora_fin' => $request->hora_fin
                    ]
                ]
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al asignar aula',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function getDisponibilidadAulas(Request $request): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $capacidad_minima = $request->query('capacidad_minima', 0);

            $aulas = DB::table('aulas')
                ->where('estado', 'disponible')
                ->where('capacidad_pupitres', '>=', $capacidad_minima)
                ->select('id', 'codigo', 'nombre', 'capacidad_pupitres', 'ubicacion')
                ->orderBy('codigo')
                ->get();

            $horarios_ocupados = DB::table('horarios')
                ->join('grupos', 'horarios.grupo_id', '=', 'grupos.id')
                ->join('materias', 'grupos.materia_id', '=', 'materias.id')
                ->select(
                    'horarios.aula_id',
                    'horarios.dia_semana',
                    DB::raw("DATE_FORMAT(horarios.hora_inicio, '%H:%i') as hora_inicio"),
                    DB::raw("DATE_FORMAT(horarios.hora_fin, '%H:%i') as hora_fin"),
                    'grupos.numero_grupo',
                    'materias.nombre as materia_nombre'
                )
                ->get();

            $disponibilidad = [];
            
            foreach ($aulas as $aula) {
                $ocupaciones = $horarios_ocupados->filter(function($h) use ($aula) {
                    return $h->aula_id == $aula->id;
                })->map(function($h) {
                    return [
                        'dia' => $h->dia_semana,
                        'hora_inicio' => $h->hora_inicio, 
                        'hora_fin' => $h->hora_fin,     
                        'grupo' => $h->numero_grupo,
                        'materia' => $h->materia_nombre
                    ];
                })->values();

                $disponibilidad[] = [
                    'aula' => [
                        'id' => $aula->id,
                        'codigo' => $aula->codigo,
                        'nombre' => $aula->nombre ?? $aula->codigo,
                        'capacidad' => $aula->capacidad_pupitres,
                        'ubicacion' => $aula->ubicacion
                    ],
                    'horarios_ocupados' => $ocupaciones
                ];
            }

            return response()->json([
                'message' => 'Disponibilidad obtenida exitosamente',
                'success' => true,
                'data' => $disponibilidad
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener disponibilidad',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}