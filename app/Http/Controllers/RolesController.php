<?php

namespace App\Http\Controllers;

use App\Models\roles;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RolesController extends Controller {
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check() || $user_rol != 1) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $roles = roles::all();

        if ($roles->isEmpty()) {
            return response()->json([
                'message' => 'No se encontraron roles',
                'success' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Roles encontrados',
            'success' => true,
            'data' => $roles
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check() || $user_rol != 1) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $rules = [
            'nombre' => 'required|string|max:255|unique:roles,nombre',
            'descripcion' => 'required|string|max:500',
        ];

        $messages = [
            'nombre.required' => 'El nombre del rol es obligatorio.',
            'nombre.string' => 'El nombre del rol debe ser una cadena de texto.',
            'nombre.max' => 'El nombre del rol no debe exceder los 255 caracteres.',
            'nombre.unique' => 'El nombre del rol ya existe.',
            'descripcion.required' => 'La descripción del rol es obligatoria.',
            'descripcion.string' => 'La descripción del rol debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción del rol no debe exceder los 500 caracteres.',
        ];

        try {
            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()){
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                    'success' => false
                ], 422);
            }

            $rol = roles::create([
                'nombre' => $validator['nombre'],
                'descripcion' => $validator['descripcion'],
            ]);

            return response()->json([
                'message' => 'Rol creado exitosamente',
                'success' => true,
                'data' => $rol
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
                'success' => false
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al crear el rol',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $rol_id): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check() || $user_rol != 1) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $rol = roles::find($rol_id);

        if (!$rol) {
            return response()->json([
                'message' => 'No se encontró el rol',
                'success' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Rol encontrado',
            'success' => true,
            'data' => $rol
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, int $rol_id): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check() || $user_rol != 1) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $rules = [
            'nombre' => 'sometimes|required|string|max:255|unique:roles,nombre,' . $rol_id,
            'descripcion' => 'sometimes|required|string|max:500',
        ];

        $messages = [
            'nombre.required' => 'El nombre del rol es obligatorio.',
            'nombre.string' => 'El nombre del rol debe ser una cadena de texto.',
            'nombre.max' => 'El nombre del rol no debe exceder los 255 caracteres.',
            'nombre.unique' => 'El nombre del rol ya existe.',
            'descripcion.required' => 'La descripción del rol es obligatoria.',
            'descripcion.string' => 'La descripción del rol debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción del rol no debe exceder los 500 caracteres.',
        ];

        try {
            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()){
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                    'success' => false
                ], 422);
            }

            $rol = roles::find($rol_id);

            if (!$rol) {
                return response()->json([
                    'message' => 'No se encontró el rol',
                    'success' => false
                ], 404);
            }

            if($request->has('nombre')) {
                $rol->nombre = $request->input('nombre');
            }

            if($request->has('descripcion')) {
                $rol->descripcion = $request->input('descripcion');
            }

            $rol->save();

            return response()->json([
                'message' => 'Rol actualizado exitosamente',
                'success' => true,
                'data' => $rol
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el rol',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $rol_id): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check() || $user_rol != 1) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $rol = roles::find($rol_id);

            if (!$rol) {
                return response()->json([
                    'message' => 'No se encontró el rol',
                    'success' => false
                ], 404);
            }

            $rol->delete();

            return response()->json([
                'message' => 'Rol eliminado exitosamente',
                'success' => true
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el rol',
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
}
