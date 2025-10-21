<?php

namespace App\Http\Controllers;

use App\Models\ciclos_academicos;
use App\RolesEnum;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CiclosAcademicosController extends Controller {
    /**
     * Display a listing of the resource.
     */
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
        $ciclos_academicos = ciclos_academicos::all();

        // ← AGREGAR ESTA VALIDACIÓN
        if ($ciclos_academicos->isEmpty()) {
            return response()->json([
                'message' => 'No se encontraron ciclos académicos',
                'success' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Ciclos académicos obtenidos exitosamente',
            'success' => true,
            'data' => $ciclos_academicos
        ], 200);
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Error al obtener los ciclos académicos',
            'error' => $e->getMessage(),
            'success' => false
        ], 500);
    }
}

    /**
     * Store a newly created resource in storage.
     */
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
            RolesEnum::COORDINADOR_CARRERAS->value,
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        $request->merge([
            'nombre' => $this->sanitizeInput($request->input('nombre')),
            'anio' => $this->sanitizeInput($request->input('anio')),
            'numero_ciclo' => $this->sanitizeInput($request->input('numero_ciclo')),
            'fecha_inicio' => $this->sanitizeInput($request->input('fecha_inicio')),
            'fecha_fin' => $this->sanitizeInput($request->input('fecha_fin')),
            'estado' => $this->sanitizeInput($request->input('estado')),
        ]);

        $rules = [
            'nombre' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-\s]+$/'],
            'anio' => 'required|integer|min:2000|max:2100',
            'numero_ciclo' => 'required|integer|min:1|max:10',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'estado' => 'required|in:planificado,activo,finalizado',
        ];

        $messages = [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no debe exceder los 255 caracteres.',
            'nombre.regex' => 'El nombre solo debe contener letras y números, sin espacios ni caracteres especiales.',
            'anio.required' => 'El año es obligatorio.',
            'anio.integer' => 'El año debe ser un número entero.',
            'anio.min' => 'El año no puede ser menor a 2000.',
            'anio.max' => 'El año no puede ser mayor a 2100.',
            'numero_ciclo.required' => 'El número de ciclo es obligatorio.',
            'numero_ciclo.integer' => 'El número de ciclo debe ser un número entero.',
            'numero_ciclo.min' => 'El número de ciclo no puede ser menor a 1.',
            'numero_ciclo.max' => 'El número de ciclo no puede ser mayor a 10.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser "planificado", "activo" o "finalizado".',
        ];

        try {
            $validatedData = $request->validate($rules, $messages);

            DB::beginTransaction();

            $ciclo_academico = ciclos_academicos::create($validatedData);

            DB::commit();

            return response()->json([
                'message' => 'Ciclo académico creado exitosamente',
                'success' => true,
                'data' => $ciclo_academico
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear el ciclo académico',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $ciclos_academicos) {
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
            $ciclo_academico = ciclos_academicos::find($ciclos_academicos);

            if (!$ciclo_academico) {
                return response()->json([
                    'message' => 'Ciclo académico no encontrado',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Ciclo académico obtenido exitosamente',
                'success' => true,
                'data' => $ciclo_academico
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el ciclo académico',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, int $ciclo_academico_id): JsonResponse {
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
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        $request->merge([
            'nombre' => $this->sanitizeInput($request->input('nombre')),
            'anio' => $this->sanitizeInput($request->input('anio')),
            'numero_ciclo' => $this->sanitizeInput($request->input('numero_ciclo')),
            'fecha_inicio' => $this->sanitizeInput($request->input('fecha_inicio')),
            'fecha_fin' => $this->sanitizeInput($request->input('fecha_fin')),
            'estado' => $this->sanitizeInput($request->input('estado')),
        ]);

        $rules = [
           'nombre' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-\s]+$/'],
            'anio' => 'required|integer|min:2000|max:2100',
            'numero_ciclo' => 'required|integer|min:1|max:10',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'estado' => 'required|in:planificado,activo,finalizado',
        ];

        $messages = [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no debe exceder los 255 caracteres.',
            'nombre.regex' => 'El nombre solo debe contener letras y números, sin espacios ni caracteres especiales.',
            'anio.required' => 'El año es obligatorio.',
            'anio.integer' => 'El año debe ser un número entero.',
            'anio.min' => 'El año no puede ser menor a 2000.',
            'anio.max' => 'El año no puede ser mayor a 2100.',
            'numero_ciclo.required' => 'El número de ciclo es obligatorio.',
            'numero_ciclo.integer' => 'El número de ciclo debe ser un número entero.',
            'numero_ciclo.min' => 'El número de ciclo no puede ser menor a 1.',
            'numero_ciclo.max' => 'El número de ciclo no puede ser mayor a 10.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser "planificado", "activo" o "finalizado".',
        ];

        try {
            $validatedData = $request->validate($rules, $messages);

            $ciclo_academico = ciclos_academicos::find($ciclo_academico_id);
            if (!$ciclo_academico) {
                return response()->json([
                    'message' => 'Ciclo académico no encontrado',
                    'success' => false
                ], 404);
            }

            DB::beginTransaction();

            $ciclo_academico->update($validatedData);

            DB::commit();

            return response()->json([
                'message' => 'Ciclo académico actualizado exitosamente',
                'success' => true,
                'data' => $ciclo_academico
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar el ciclo académico',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $ciclo_academico_id): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user_rolName = $this->getUserRoleName();

        if ($user_rolName != RolesEnum::ADMINISTRADOR_ACADEMICO->value) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $ciclo_academico = ciclos_academicos::find($ciclo_academico_id);
            if (!$ciclo_academico) {
                return response()->json([
                    'message' => 'Ciclo académico no encontrado',
                    'success' => false
                ], 404);
            }

            DB::beginTransaction();

            $ciclo_academico->delete();

            DB::commit();

            return response()->json([
                'message' => 'Ciclo académico eliminado exitosamente',
                'success' => true
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar el ciclo académico',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function getByStatus(string $estado): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $estado = $this->sanitizeInput($estado);
            if (!in_array($estado, ['planificado', 'activo', 'finalizado'])) {
                return response()->json([
                    'message' => 'Estado inválido. Debe ser: "planificado", "activo" o "finalizado".',
                    'success' => false
                ], 400);
            }

            $ciclos_academicos = ciclos_academicos::where('estado', $estado)->get();

            if ($ciclos_academicos->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron ciclos académicos con el estado especificado',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Ciclos académicos obtenidos exitosamente',
                'success' => true,
                'data' => $ciclos_academicos
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los ciclos académicos por estado',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function getCurrentTerm(): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $currentDate = date('Y-m-d');
            $currentTerm = ciclos_academicos::where('fecha_inicio', '<=', $currentDate)
                ->where('fecha_fin', '>=', $currentDate)
                ->first();

            if (!$currentTerm) {
                return response()->json([
                    'message' => 'No hay un ciclo académico activo en este momento',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Ciclo académico actual obtenido exitosamente',
                'success' => true,
                'data' => $currentTerm
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el ciclo académico actual',
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
