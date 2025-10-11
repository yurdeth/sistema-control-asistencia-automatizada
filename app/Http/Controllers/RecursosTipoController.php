<?php

namespace App\Http\Controllers;

use App\Models\recursos_tipo;
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

        $user_rol = $this->getUserRole();
        // Disponible solamente para: Administradores (1), Administrador académico (2), Jefe de departamentos (3) y Docentes (4)
        if ($user_rol > 4) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $recursos_tipos = Cache::remember('recursos_tipos', 60, function () {
                return recursos_tipo::limit(10)->get();
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

        $user_rol = $this->getUserRole();
        // Disponible solamente para: Administradores (1), Administrador académico (2) y Jefe de departamentos (3)
        if ($user_rol > 3) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $request->merge([
            'nombre' => $this->sanitizeInput($request->input('nombre')),
            'descripcion' => $this->sanitizeInput($request->input('descripcion')),
            'icono' => $this->sanitizeInput($request->input('icono'))
        ]);

        $rules = [
            'nombre' => 'required|string|max:255|unique:recursos_tipos,nombre',
            'descripcion' => 'required|string|max:1000',
            'icono' => 'nullable|string|max:255'
        ];

        $messages = [
            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.string' => 'El campo nombre debe ser una cadena de texto.',
            'nombre.max' => 'El campo nombre no debe exceder los 255 caracteres.',
            'nombre.unique' => 'El nombre ya está en uso.',
            'descripcion.required' => 'El campo descripción es obligatorio.',
            'descripcion.string' => 'El campo descripción debe ser una cadena de texto.',
            'descripcion.max' => 'El campo descripción no debe exceder los 1000 caracteres.',
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

        $user_rol = $this->getUserRole();
        // Disponible solamente para: Administradores (1), Administrador académico (2) y Jefe de departamentos (3)
        if ($user_rol > 3) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
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

        $user_rol = $this->getUserRole();
        // Disponible solamente para: Administradores (1), Administrador académico (2) y Jefe de departamentos (3)
        if ($user_rol > 3) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
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
            'nombre' => 'required|string|max:255|unique:recursos_tipos,nombre',
            'descripcion' => 'required|string|max:1000',
            'icono' => 'nullable|string|max:255'
        ];

        $messages = [
            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.string' => 'El campo nombre debe ser una cadena de texto.',
            'nombre.max' => 'El campo nombre no debe exceder los 255 caracteres.',
            'nombre.unique' => 'El nombre ya está en uso.',
            'descripcion.required' => 'El campo descripción es obligatorio.',
            'descripcion.string' => 'El campo descripción debe ser una cadena de texto.',
            'descripcion.max' => 'El campo descripción no debe exceder los 1000 caracteres.',
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

        $user_rol = $this->getUserRole();
        // Disponible solamente para: Administradores (1), Administrador académico (2) y Jefe de departamentos (3)
        if ($user_rol > 3) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
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

    private function getUserRole() {
        return DB::table('usuario_roles')
            ->join('users', 'usuario_roles.usuario_id', '=', 'users.id')
            ->where('users.id', Auth::id())
            ->value('usuario_roles.rol_id');
    }

    private function sanitizeInput($input): string {
        return htmlspecialchars(strip_tags(trim($input)));
    }
}
