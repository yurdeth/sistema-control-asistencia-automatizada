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

        $grupos = Cache::remember('grupos_all', 60, function () {
            return grupos::limit(50)->get();
        });

        if ($grupos->isEmpty()) {
            return response()->json([
                'message' => 'No hay grupos disponibles',
                'success' => true
            ], 404);
        }

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

        $grupo = grupos::find($id);

        if (!$grupo) {
            return response()->json([
                'message' => 'Grupo no encontrado',
                'success' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Grupo obtenido exitosamente',
            'success' => true,
            'data' => $grupo
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
            RolesEnum::DOCENTE->value,
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        $docente_id_existe = DB::table('usuario_roles')
            ->where('usuario_id', $request->docente_id)
            ->where('rol_id', 5)
            ->value('usuario_id');

        if (!$docente_id_existe) {
            return response()->json([
                'message' => 'Error: este docente no existe o no tiene el rol adecuado.',
                'success' => false
            ], 422);
        }

        $request->merge([
            'materia_id' => $this->sanitizeInput($request->materia_id),
            'ciclo_id' => $this->sanitizeInput($request->ciclo_id),
            'docente_id' => $this->sanitizeInput($request->docente_id),
            'numero_grupo' => $this->sanitizeInput($request->numero_grupo),
            'capacidad_maxima' => $this->sanitizeInput($request->capacidad_maxima),
            'estado' => $this->sanitizeInput($request->estado)
        ]);

        $rules = [
            'materia_id' => 'required|exists:materias,id',
            'ciclo_id' => 'required|exists:ciclos_academicos,id',
            'docente_id' => 'required|exists:users,id',
            'numero_grupo' => 'required|string|max:10',
            'capacidad_maxima' => 'required|integer|min:1',
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

            DB::beginTransaction();

            $validatedData = $validator->validated();

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

            $grupo = grupos::create($validatedData);

            DB::commit();
            Cache::forget('grupos_all');

            return response()->json([
                'message' => 'Grupo creado exitosamente',
                'success' => true,
                'data' => $grupo
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
            RolesEnum::DOCENTE->value,
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

        $docente_id_existe = DB::table('usuario_roles')
            ->where('usuario_id', $request->docente_id)
            ->where('rol_id', 5)
            ->value('usuario_id');

        if (!$docente_id_existe) {
            return response()->json([
                'message' => 'Error: este docente no existe o no tiene el rol adecuado.',
                'success' => false
            ], 422);
        }

        $request->merge([
            'materia_id' => $this->sanitizeInput($request->materia_id),
            'ciclo_id' => $this->sanitizeInput($request->ciclo_id),
            'docente_id' => $this->sanitizeInput($request->docente_id),
            'numero_grupo' => $this->sanitizeInput($request->numero_grupo),
            'capacidad_maxima' => $this->sanitizeInput($request->capacidad_maxima),
            'estado' => $this->sanitizeInput($request->estado)
        ]);

        $rules = [
            'materia_id' => 'required|exists:materias,id',
            'ciclo_id' => 'required|exists:ciclos_academicos,id',
            'docente_id' => 'required|exists:users,id',
            'numero_grupo' => 'required|string|max:10',
            'capacidad_maxima' => 'required|integer|min:1',
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

            DB::beginTransaction();

            $validatedData = $validator->validated();

            $grupo->update($validatedData);

            DB::commit();
            Cache::forget('grupos_all');

            return response()->json([
                'message' => 'Grupo actualizado exitosamente',
                'success' => true,
                'data' => $grupo
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
            $grupo = grupos::where('id', $id)->lockForUpdate()->first();

            if (!$grupo) {
                return response()->json([
                    'message' => 'Grupo no encontrado',
                    'success' => false
                ], 404);
            }

            $grupo->delete();
            Cache::forget('grupos_all');

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
            $grupos = (new grupos())->getGruposByMateria($id);

            if ($grupos->isEmpty()) {
                return response()->json([
                    'message' => 'No hay grupos disponibles para esta materia',
                    'success' => true
                ], 404);
            }

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
            $grupos = (new grupos())->getGruposByCiclo($id);

            if ($grupos->isEmpty()) {
                return response()->json([
                    'message' => 'No hay grupos disponibles para este ciclo',
                    'success' => true
                ], 404);
            }

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
            $grupos = (new grupos())->getGruposByDocente($id);

            if ($grupos->isEmpty()) {
                return response()->json([
                    'message' => 'No hay grupos disponibles para este ciclo',
                    'success' => true
                ], 404);
            }

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
            $grupos = (new grupos())->getGruposByEstado($estado);

            if ($grupos->isEmpty()) {
                return response()->json([
                    'message' => 'No hay grupos disponibles para este ciclo',
                    'success' => true
                ], 404);
            }

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
            $grupos = (new grupos())->getGruposDisponibles();

            if ($grupos->isEmpty()) {
                return response()->json([
                    'message' => 'No hay grupos disponibles para este ciclo',
                    'success' => true
                ], 404);
            }

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
