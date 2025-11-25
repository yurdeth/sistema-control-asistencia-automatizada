<?php

namespace App\Http\Controllers;

use App\Models\recursos_tipo;
use App\RolesEnum;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RecursosTipoController extends Controller {
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

        try {
//            $recursos_tipos = Cache::remember('recursos_tipos', 60, function () {
//                return recursos_tipo::limit(10)->get();
//            });

            $recursos_tipos = Cache::remember('recursos_tipos', 5, function () {
                return recursos_tipo::all();
            });

            return response()->json([
                'message' => 'Recursos obtenidos exitosamente',
                'success' => true,
                'data' => $recursos_tipos
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener recursos',
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
            RolesEnum::ROOT->value,
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
            'nombre' => $this->sanitizeInput($request->input('nombre')),
            'descripcion' => $this->sanitizeInput($request->input('descripcion')),
            'icono' => $this->sanitizeInput($request->input('icono'))
        ]);

        $rules = [
            'nombre' => ['required', 'string', 'max:255', 'unique:recursos_tipos,nombre', 'regex:/^[a-zA-ZñÑáÁéÉíÍóÓúÚ\s]+$/u'],
            'descripcion' => ['required', 'string', 'max:1000', 'regex:/^[a-zA-ZñÑáÁéÉíÍóÓúÚ\s]+$/u'],
            'icono' => 'nullable|string|max:255'
        ];

        $messages = [
            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.string' => 'El campo nombre debe ser una cadena de texto.',
            'nombre.max' => 'El campo nombre no debe exceder los 255 caracteres.',
            'nombre.unique' => 'El nombre ya está en uso.',
            'nombre.regex' => 'El campo nombre solo debe contener letras y espacios.',
            'descripcion.required' => 'El campo descripción es obligatorio.',
            'descripcion.string' => 'El campo descripción debe ser una cadena de texto.',
            'descripcion.max' => 'El campo descripción no debe exceder los 1000 caracteres.',
            'descripcion.regex' => 'El campo descripción solo debe contener letras y espacios.',
            'icono.string' => 'El campo icono debe ser una cadena de texto.',
            'icono.max' => 'El campo icono no debe exceder los 255 caracteres.'
        ];

        try {
            $validatedData = $request->validate($rules, $messages);

            DB::beginTransaction();

            $recursos_tipo = recursos_tipo::create([
                'nombre' => $validatedData['nombre'],
                'descripcion' => $validatedData['descripcion'],
                'icono' => $validatedData['icono'] ?? null
            ]);

            DB::commit();

            // eliminar cache despues de la insercion
            Cache::forget('recursos_tipos');

            return response()->json([
                'message' => 'Recurso agregado exitosamente',
                'success' => true,
                'data' => $recursos_tipo
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al agregar recurso',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $recursos_tipo_id): JsonResponse {
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
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $recursos_tipo = recursos_tipo::find($recursos_tipo_id);
            if (!$recursos_tipo) {
                return response()->json([
                    'message' => 'Recurso no encontrado',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Recurso obtenido exitosamente',
                'success' => true,
                'data' => $recursos_tipo
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener recurso',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, int $recursos_tipo_id): JsonResponse {
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
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        $recursos_tipo = recursos_tipo::find($recursos_tipo_id);
        if (!$recursos_tipo) {
            return response()->json([
                'message' => 'Recurso no encontrado',
                'success' => false
            ], 404);
        }

        $request->merge([
            'nombre' => $this->sanitizeInput($request->input('nombre')),
            'descripcion' => $this->sanitizeInput($request->input('descripcion')),
            'icono' => $this->sanitizeInput($request->input('icono'))
        ]);

        $rules = [
            'nombre' => ['required', 'string', 'max:255', 'unique:recursos_tipos,nombre,' . $recursos_tipo_id, 'regex:/^[a-zA-ZñÑáÁéÉíÍóÓúÚ\s]+$/u'],
            'descripcion' => ['required', 'string', 'max:1000', 'regex:/^[a-zA-ZñÑáÁéÉíÍóÓúÚ\s]+$/u'],
            'icono' => 'nullable|string|max:255'
        ];

        $messages = [
            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.string' => 'El campo nombre debe ser una cadena de texto.',
            'nombre.max' => 'El campo nombre no debe exceder los 255 caracteres.',
            'nombre.unique' => 'El nombre ya está en uso.',
            'nombre.regex' => 'El campo nombre solo debe contener letras y espacios.',
            'descripcion.required' => 'El campo descripción es obligatorio.',
            'descripcion.string' => 'El campo descripción debe ser una cadena de texto.',
            'descripcion.max' => 'El campo descripción no debe exceder los 1000 caracteres.',
            'descripcion.regex' => 'El campo descripción solo debe contener letras y espacios.',
            'icono.string' => 'El campo icono debe ser una cadena de texto.',
            'icono.max' => 'El campo icono no debe exceder los 255 caracteres.'
        ];

        try {
            $validatedData = $request->validate($rules, $messages);

            DB::beginTransaction();

            if($request->has('nombre')) {
                $recursos_tipo->nombre = $validatedData['nombre'];
            }

            if($request->has('descripcion')) {
                $recursos_tipo->descripcion = $validatedData['descripcion'];
            }

            if($request->has('icono')) {
                $recursos_tipo->icono = $validatedData['icono'] ?? null;
            }

            $recursos_tipo->save();

            DB::commit();

            return response()->json([
                'message' => 'Recurso actualizado exitosamente',
                'success' => true,
                'data' => $recursos_tipo
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar recurso',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $recursos_tipo_id): JsonResponse {
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
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $recursos_tipo = recursos_tipo::where('id', $recursos_tipo_id)->lockForUpdate()->first();
            if (!$recursos_tipo) {
                return response()->json([
                    'message' => 'Recurso no encontrado',
                    'success' => false
                ], 404);
            }

            DB::beginTransaction();

            $recursos_tipo->delete();

            DB::commit();

            return response()->json([
                'message' => 'Recurso eliminado exitosamente',
                'success' => true
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar recurso',
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
