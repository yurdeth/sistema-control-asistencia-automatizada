<?php

namespace App\Http\Controllers;

use App\Models\departamentos;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DepartamentosController extends Controller {
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse {
        try {
            $departamentos = Cache::remember('departamentos_all', 60, function () {
                return departamentos::all();
            });

            if ($departamentos->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron departamentos',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $departamentos,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al obtener los departamentos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check() || $user_rol == 5 || $user_rol == 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $rules = [
            'nombre' => 'required|string|max:255|unique:departamentos,nombre',
            'descripcion' => 'required|string|max:500',
            'estado' => 'in:activo,inactivo',
        ];

        $messages = [
            'nombre.required' => 'El nombre del departamento es obligatorio.',
            'nombre.string' => 'El nombre del departamento debe ser una cadena de texto.',
            'nombre.max' => 'El nombre del departamento no debe exceder los 255 caracteres.',
            'nombre.unique' => 'El nombre del departamento ya está en uso.',
            'descripcion.string' => 'La descripción del departamento debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción del departamento no debe exceder los 500 caracteres.',
            'estado.required' => 'El estado del departamento es obligatorio.',
            'estado.in' => 'El estado del departamento debe ser "activo" o "inactivo".',
        ];

        try {
            $validation = Validator::make($request->all(), $rules, $messages);

            if ($validation->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validation->errors(),
                    'success' => false
                ], 422);
            }

            DB::beginTransaction();
            $departamento = departamentos::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'estado' => $request->estado ?? 'activo',
            ]);

            DB::commit();
            Cache::forget('departamentos_all');

            return response()->json([
                'message' => 'Departamento creado exitosamente',
                'success' => true,
                'data' => $departamento
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear el departamento',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $departamento_id): JsonResponse {
        try {
            $departamento = departamentos::find($departamento_id);

            if (!$departamento) {
                return response()->json([
                    'message' => 'Departamento no encontrado',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $departamento,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al obtener el departamento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, int $departament_id): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check() || $user_rol == 5 || $user_rol == 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $rules = [
            'nombre' => 'string|max:255|unique:departamentos,nombre,' . $departament_id,
            'descripcion' => 'string|max:500',
            'estado' => 'in:activo,inactivo',
        ];
        $messages = [
            'nombre.required' => 'El nombre del departamento es obligatorio.',
            'nombre.string' => 'El nombre del departamento debe ser una cadena de texto.',
            'nombre.max' => 'El nombre del departamento no debe exceder los 255 caracteres.',
            'nombre.unique' => 'El nombre del departamento ya está en uso.',
            'descripcion.string' => 'La descripción del departamento debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción del departamento no debe exceder los 500 caracteres.',
            'estado.in' => 'El estado del departamento debe ser "activo" o "inactivo".',
        ];

        try {
            $validation = Validator::make($request->all(), $rules, $messages);

            if ($validation->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validation->errors(),
                    'success' => false
                ], 422);
            }

            DB::beginTransaction();
            $departamento = departamentos::where('id', $departament_id)->lockForUpdate()->first();

            if (!$departamento) {
                return response()->json([
                    'message' => 'Departamento no encontrado',
                    'success' => false
                ], 404);
            }

            if ($request->has('nombre')) {
                $departamento->nombre = $request->nombre;
            }
            if ($request->has('descripcion')) {
                $departamento->descripcion = $request->descripcion;
            }
            if ($request->has('estado')) {
                $departamento->estado = $request->estado;
            }

            $departamento->save();
            DB::commit();

            return response()->json([
                'message' => 'Departamento actualizado exitosamente',
                'success' => true,
                'data' => $departamento
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar el departamento',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $departament_id) {
        $user_rol = $this->getUserRole();

        if (!Auth::check() || $user_rol == 5 || $user_rol == 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            DB::beginTransaction();
            $departamento = departamentos::find($departament_id);

            if (!$departamento) {
                return response()->json([
                    'message' => 'Departamento no encontrado',
                    'success' => false
                ], 404);
            }

            $departamento->delete();
            DB::commit();

            return response()->json([
                'message' => 'Departamento eliminado exitosamente',
                'success' => true
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar el departamento',
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

    private function sanitizeInputs(string $value): string {
        return htmlspecialchars(trim(strip_tags($value)));
    }
}
