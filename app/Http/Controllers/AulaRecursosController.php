<?php

namespace App\Http\Controllers;

use App\Models\aula_recursos;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AulaRecursosController extends Controller {

    public function index(): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $recursos = Cache::remember('aula_recursos_all', 60, function() {
                return (new aula_recursos())->getAllAulaRecursos();
            });

            if (!$recursos || $recursos->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron recursos',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'data' => $recursos,
                'message' => 'Recursos obtenidos exitosamente',
                'success' => true
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los recursos',
                'success' => false,
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

        try{
            $recurso = (new aula_recursos())->getAllAulaRecursoById($id)->first();

            if (!$recurso) {
                return response()->json([
                    'message' => 'Recurso no encontrado',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'data' => $recurso,
                'message' => 'Recurso obtenido exitosamente',
                'success' => true
            ], 200);
        } catch (Exception $e){
            return response()->json([
                'message' => 'Error al obtener el recurso',
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

        $user_rol = $this->getUserRole();
        if ($user_rol >= 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $request->merge([
            'aula_id' => $this->sanitizeInput($request->aula_id),
            'recurso_tipo_id' => $this->sanitizeInput($request->recurso_tipo_id),
            'cantidad' => $this->sanitizeInput($request->cantidad),
            'estado' => $this->sanitizeInput($request->estado),
            'observaciones' => $this->sanitizeInput($request->observaciones ?? null)
        ]);

        $rules = [
            'aula_id' => 'required|exists:aulas,id',
            'recurso_tipo_id' => 'required|exists:recursos_tipos,id',
            'cantidad' => 'required|integer|min:1',
            'estado' => 'required|in:nuevo,bueno,regular,malo,mantenimiento',
            'observaciones' => 'nullable|string'
        ];

        $messages = [
            'aula_id.required' => 'El campo aula_id es obligatorio.',
            'aula_id.exists' => 'El aula especificada no existe.',
            'recurso_tipo_id.required' => 'El campo recurso_tipo_id es obligatorio.',
            'recurso_tipo_id.exists' => 'El tipo de recurso especificado no existe.',
            'cantidad.required' => 'El campo cantidad es obligatorio.',
            'cantidad.integer' => 'El campo cantidad debe ser un número entero.',
            'cantidad.min' => 'El campo cantidad debe ser al menos 1.',
            'estado.required' => 'El campo estado es obligatorio.',
            'estado.in' => 'El campo estado debe ser uno de los siguientes valores: nuevo, bueno, regular, malo, mantenimiento.',
            'observaciones.string' => 'El campo observaciones debe ser una cadena de texto.'
        ];

        try {
            DB::beginTransaction();

            $validatedData = $request->validate($rules, $messages);

            $recursoExistente = aula_recursos::where('aula_id', $request->aula_id)
                ->where('recurso_tipo_id', $request->recurso_tipo_id)
                ->first();

            if ($recursoExistente) {
                return response()->json([
                    'message' => 'El recurso ya existe en el aula especificada.',
                    'success' => false
                ], 409);
            }

            $nuevoRecurso = aula_recursos::create($validatedData);

            DB::commit();

            return response()->json([
                'data' => $nuevoRecurso,
                'message' => 'Recurso creado exitosamente',
                'success' => true
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear el recurso',
                'success' => false,
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

        $user_rol = $this->getUserRole();
        if ($user_rol >= 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $request->merge([
            'aula_id' => $this->sanitizeInput($request->aula_id),
            'recurso_tipo_id' => $this->sanitizeInput($request->recurso_tipo_id),
            'cantidad' => $this->sanitizeInput($request->cantidad),
            'estado' => $this->sanitizeInput($request->estado),
            'observaciones' => $this->sanitizeInput($request->observaciones ?? null)
        ]);

        $rules = [
            'aula_id' => 'required|exists:aulas,id',
            'recurso_tipo_id' => 'required|exists:recursos_tipo,id',
            'cantidad' => 'required|integer|min:1',
            'estado' => 'required|in:operativo,en_reparacion,fuera_de_servicio',
            'observaciones' => 'nullable|string'
        ];

        $messages = [
            'aula_id.required' => 'El campo aula_id es obligatorio.',
            'aula_id.exists' => 'El aula especificada no existe.',
            'recurso_tipo_id.required' => 'El campo recurso_tipo_id es obligatorio.',
            'recurso_tipo_id.exists' => 'El tipo de recurso especificado no existe.',
            'cantidad.required' => 'El campo cantidad es obligatorio.',
            'cantidad.integer' => 'El campo cantidad debe ser un número entero.',
            'cantidad.min' => 'El campo cantidad debe ser al menos 1.',
            'estado.required' => 'El campo estado es obligatorio.',
            'estado.in' => 'El campo estado debe ser uno de los siguientes valores: operativo, en_reparacion, fuera_de_servicio.',
            'observaciones.string' => 'El campo observaciones debe ser una cadena de texto.'
        ];

        try {
            DB::beginTransaction();

            $validatedData = $request->validate($rules, $messages);

            $recurso = aula_recursos::find($id);

            if (!$recurso) {
                return response()->json([
                    'message' => 'Recurso no encontrado',
                    'success' => false
                ], 404);
            }

            $recursoExistente = aula_recursos::where('aula_id', $request->aula_id)
                ->where('recurso_tipo_id', $request->recurso_tipo_id)
                ->where('id', '!=', $id)
                ->first();

            if ($recursoExistente) {
                return response()->json([
                    'message' => 'El recurso ya existe en el aula especificada.',
                    'success' => false
                ], 409);
            }

            $recurso->update($validatedData);

            DB::commit();

            return response()->json([
                'data' => $recurso,
                'message' => 'Recurso actualizado exitosamente',
                'success' => true
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar el recurso',
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

        $user_rol = $this->getUserRole();
        if ($user_rol >= 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            DB::beginTransaction();
            $recurso = aula_recursos::where('id', $id)->lockForUpdate()->first();

            if (!$recurso) {
                return response()->json([
                    'message' => 'Recurso no encontrado',
                    'success' => false
                ], 404);
            }

            $recurso->delete();

            DB::commit();

            return response()->json([
                'message' => 'Recurso eliminado exitosamente',
                'success' => true
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar el recurso',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getResourcesByClassroom($aula_id): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $recursos = aula_recursos::with(['recursoTipo'])
                ->where('aula_id', $aula_id)
                ->get();

            if ($recursos->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron recursos para el aula especificada',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'data' => $recursos,
                'message' => 'Recursos obtenidos exitosamente',
                'success' => true
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los recursos',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getClassroomsByResource($recurso_tipo_id): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $aulas = DB::table('aulas')
                ->join('aula_recursos', 'aulas.id', '=', 'aula_recursos.aula_id')
                ->where('aula_recursos.recurso_tipo_id', $recurso_tipo_id)
                ->select('aulas.*', 'aula_recursos.cantidad', 'aula_recursos.estado')
                ->get();

            if ($aulas->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron aulas con el recurso especificado',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'data' => $aulas,
                'message' => 'Aulas obtenidas exitosamente',
                'success' => true
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las aulas',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getResourcesByStatus($estado): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $estado = $this->sanitizeInput($estado);

        try {
            $recursos = aula_recursos::with(['aula', 'recursoTipo'])
                ->where('estado', $estado)
                ->get();

            if ($recursos->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron recursos con el estado especificado',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'data' => $recursos,
                'message' => 'Recursos obtenidos exitosamente',
                'success' => true
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los recursos',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAvailableResourcesByClassroom($aula_id): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $recursos = aula_recursos::with(['recursoTipo'])
                ->where('aula_id', $aula_id)
                ->where('estado', 'operativo')
                ->get();

            if ($recursos->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron recursos operativos para el aula especificada',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'data' => $recursos,
                'message' => 'Recursos operativos obtenidos exitosamente',
                'success' => true
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los recursos',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function searchClassroomsByResources(Request $request): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $request->merge([
            'recursos' => array_map([$this, 'sanitizeInput'], $request->recursos ?? []),
            'cantidad_minima' => $this->sanitizeInput($request->cantidad_minima ?? '1')
        ]);

        $rules = [
            'recursos' => 'required|array',
            'recursos.*' => 'exists:recursos_tipo,id',
            'cantidad_minima' => 'sometimes|integer|min:1'
        ];

        $messages = [
            'recursos.required' => 'El campo recursos es obligatorio.',
            'recursos.array' => 'El campo recursos debe ser un arreglo.',
            'recursos.*.exists' => 'Uno o más tipos de recursos no existen.',
            'cantidad_minima.integer' => 'El campo cantidad_minima debe ser un número entero.',
            'cantidad_minima.min' => 'El campo cantidad_minima debe ser al menos 1.'
        ];

        try {
            $validatedData = $request->validate($rules, $messages);

            $aulas = DB::table('aulas')
                ->join('aula_recursos', 'aulas.id', '=', 'aula_recursos.aula_id')
                ->whereIn('aula_recursos.recurso_tipo_id', $validatedData['recursos'])
                ->where('aula_recursos.estado', 'operativo')
                ->groupBy('aulas.id', 'aulas.codigo', 'aulas.nombre', 'aulas.capacidad')
                ->havingRaw('COUNT(DISTINCT aula_recursos.recurso_tipo_id) = ?', [count($validatedData['recursos'])])
                ->havingRaw('SUM(aula_recursos.cantidad) >= ?', [$validatedData['cantidad_minima']])
                ->select('aulas.*')
                ->get();

            if ($aulas->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron aulas que cumplan con los criterios especificados',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'data' => $aulas,
                'message' => 'Aulas obtenidas exitosamente',
                'success' => true
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al buscar las aulas',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function changeResourceStatus(Request $request, $id): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $request->merge([
                'estado' => $this->sanitizeInput($request->estado)
            ]);

            $rules = [
                'estado' => 'required|in:operativo,en_reparacion,fuera_de_servicio'
            ];

            $messages = [
                'estado.required' => 'El campo estado es obligatorio.',
                'estado.in' => 'El campo estado debe ser uno de los siguientes valores: operativo, en_reparacion, fuera_de_servicio.'
            ];

            $validatedData = $request->validate($rules, $messages);

            $recurso = aula_recursos::find($id);

            if (!$recurso) {
                return response()->json([
                    'message' => 'Recurso no encontrado',
                    'success' => false
                ], 404);
            }

            $recurso->estado = $validatedData['estado'];
            $recurso->save();

            return response()->json([
                'data' => $recurso,
                'message' => 'Estado del recurso actualizado exitosamente',
                'success' => true
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el estado del recurso',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getInventory(Request $request): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user_rol = $this->getUserRole();
        if ($user_rol >= 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $request->merge([
                'aula_id' => $this->sanitizeInput($request->aula_id ?? null),
                'recurso_tipo_id' => $this->sanitizeInput($request->recurso_tipo_id ?? null),
                'estado' => $this->sanitizeInput($request->estado ?? null)
            ]);

            $rules = [
                'aula_id' => 'sometimes|exists:aulas,id',
                'recurso_tipo_id' => 'sometimes|exists:recursos_tipo,id',
                'estado' => 'sometimes|in:operativo,en_reparacion,fuera_de_servicio'
            ];

            $messages = [
                'aula_id.exists' => 'El aula especificada no existe.',
                'recurso_tipo_id.exists' => 'El tipo de recurso especificado no existe.',
                'estado.in' => 'El campo estado debe ser uno de los siguientes valores: operativo, en_reparacion, fuera_de_servicio.'
            ];

            $validatedData = $request->validate($rules, $messages);

            $query = aula_recursos::with(['aula', 'recursoTipo']);

            if (isset($validatedData['aula_id'])) {
                $query->where('aula_id', $validatedData['aula_id']);
            }

            if (isset($validatedData['recurso_tipo_id'])) {
                $query->where('recurso_tipo_id', $validatedData['recurso_tipo_id']);
            }

            if (isset($validatedData['estado'])) {
                $query->where('estado', $validatedData['estado']);
            }

            $recursos = $query->get();

            if ($recursos->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron recursos que cumplan con los criterios especificados',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'data' => $recursos,
                'message' => 'Recursos obtenidos exitosamente',
                'success' => true
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los recursos',
                'success' => false,
                'error' => $e->getMessage()
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
