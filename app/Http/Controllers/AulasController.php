<?php

namespace App\Http\Controllers;

use App\Models\aulas;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AulasController extends Controller {

    public function index(): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $aulas = (new aulas())->getAll();

        // Crear array recursos, agrupando por aula_id
        $aulas_array = [];
        foreach ($aulas as $aula) {
            $aula_id = $aula->aula_id;
            if (!isset($aulas_array[$aula_id])) {
                $aulas_array[$aula_id] = [
                    'id' => $aula->aula_id,
                    'codigo' => $aula->codigo_aula,
                    'nombre' => $aula->nombre_aula,
                    'capacidad_pupitres' => $aula->capacidad_pupitres,
                    'ubicacion' => $aula->ubicacion_aula,
                    'qr_code' => $aula->qr_code,
                    'estado' => $aula->estado_aula,
                    'recursos' => []
                ];
            }
            if ($aula->recurso_tipo_nombre) {
                $aulas_array[$aula_id]['recursos'][] = [
                    'nombre' => $aula->recurso_tipo_nombre,
                    'cantidad' => $aula->recurso_cantidad,
                    'estado' => $aula->estado_recurso,
                    'observaciones_recurso' => $aula->observaciones_recurso,
                    'aula_recurso_id' => $aula->aula_id
                ];
            }
        }

        $aulas = array_values($aulas_array);

        if (empty($aulas)) {
            return response()->json([
                'message' => 'No hay aulas registradas',
                'success' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Aulas obtenidas exitosamente',
            'success' => true,
            'data' => $aulas
        ], 200);
    }

    public function show($id): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $aula = (new aulas())->getAllById($id);

        if (!$aula) {
            return response()->json([
                'message' => 'Aula no encontrada',
                'success' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Aula obtenida exitosamente',
            'success' => true,
            'data' => $aula
        ], 200);
    }

    public function store(Request $request): JsonResponse {
    if (!Auth::check()) {
        return response()->json([
            'message' => 'Acceso no autorizado',
            'success' => false
        ], 401);
    }

    $user_rol = $this->getUserRole();
  
    if ($user_rol != 2) {
        return response()->json([
            'message' => 'Acceso no autorizado',
            'success' => false
        ], 401);
    }

    $request->merge([
        'codigo' => $this->sanitizeInput($request->codigo),
        'nombre' => $this->sanitizeInput($request->nombre),
        'capacidad_pupitres' => (int)$request->capacidad_pupitres,
        'ubicacion' => $this->sanitizeInput($request->ubicacion),
        'estado' => $this->sanitizeInput($request->estado)
    ]);

    $rules = [
        'codigo' => 'required|string|max:50|unique:aulas,codigo',
        'nombre' => 'required|string|max:100',
        'capacidad_pupitres' => 'required|integer|min:1',
        'ubicacion' => 'required|string|max:255',
        'estado' => 'required|in:disponible,ocupada,mantenimiento,inactiva'
    ];

    $messages = [
        'codigo.required' => 'El campo código es obligatorio.',
        'codigo.string' => 'El campo código debe ser una cadena de texto.',
        'codigo.max' => 'El campo código no debe exceder los 50 caracteres.',
        'codigo.unique' => 'El código ya está en uso.',
        'nombre.required' => 'El campo nombre es obligatorio.',
        'nombre.string' => 'El campo nombre debe ser una cadena de texto.',
        'nombre.max' => 'El campo nombre no debe exceder los 100 caracteres.',
        'capacidad_pupitres.required' => 'El campo capacidad de pupitres es obligatorio.',
        'capacidad_pupitres.integer' => 'El campo capacidad de pupitres debe ser un número entero.',
        'capacidad_pupitres.min' => 'El campo capacidad de pupitres debe ser al menos 1.',
        'ubicacion.required' => 'El campo ubicación es obligatorio.',
        'ubicacion.string' => 'El campo ubicación debe ser una cadena de texto.',
        'ubicacion.max' => 'El campo ubicación no debe exceder los 255 caracteres.',
        'estado.required' => 'El campo estado es obligatorio.',
        'estado.in' => 'El campo estado debe ser uno de los siguientes valores: disponible, ocupada, mantenimiento, inactiva.'
    ];

    try {
        $validation = $request->validate($rules, $messages);

        DB::beginTransaction();

      
        $qrContent = url('/aulas/' . $validation['codigo']);
        
        $qrCodeSvg = QrCode::format('svg')
                          ->size(400)
                          ->errorCorrection('H')
                          ->margin(2)
                          ->generate($qrContent);

   
        $validation['qr_code'] = $qrCodeSvg;

        $aula = aulas::create($validation);

        DB::commit();

        return response()->json([
            'message' => 'Aula creada exitosamente con código QR único',
            'success' => true,
            'data' => $aula
        ], 201);

    } catch (Exception $e) {
        DB::rollBack();
        
        return response()->json([
            'message' => 'Error al crear el aula',
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
        // Solo permitir para Administrador académico
        if ($user_rol != 2) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $request->merge([
            'codigo' => $this->sanitizeInput($request->codigo),
            'nombre' => $this->sanitizeInput($request->nombre),
            'capacidad_pupitres' => (int)$request->capacidad_pupitres,
            'ubicacion' => $this->sanitizeInput($request->ubicacion),
            'qr_code' => $this->sanitizeInput($request->qr_code),
            'estado' => $this->sanitizeInput($request->estado)
        ]);

        $rules = [
            'codigo' => 'required|string|max:50|unique:aulas,codigo,' . $id,
            'nombre' => 'required|string|max:100',
            'capacidad_pupitres' => 'required|integer|min:1',
            'ubicacion' => 'required|string|max:255',
            'qr_code' => 'nullable|string|max:255|unique:aulas,qr_code,' . $id,
            'estado' => 'required|in:disponible,ocupada,mantenimiento,inactiva'
        ];

        $messages = [
            'codigo.required' => 'El campo código es obligatorio.',
            'codigo.string' => 'El campo código debe ser una cadena de texto.',
            'codigo.max' => 'El campo código no debe exceder los 50 caracteres.',
            'codigo.unique' => 'El código ya está en uso.',
            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.string' => 'El campo nombre debe ser una cadena de texto.',
            'nombre.max' => 'El campo nombre no debe exceder los 100 caracteres.',
            'capacidad_pupitres.required' => 'El campo capacidad de pupitres es obligatorio.',
            'capacidad_pupitres.integer' => 'El campo capacidad de pupitres debe ser un número entero.',
            'capacidad_pupitres.min' => 'El campo capacidad de pupitres debe ser al menos 1.',
            'ubicacion.required' => 'El campo ubicación es obligatorio.',
            'ubicacion.string' => 'El campo ubicación debe ser una cadena de texto.',
            'ubicacion.max' => 'El campo ubicación no debe exceder los 255 caracteres.',
            'qr_code.string' => 'El campo QR code debe ser una cadena de texto.',
            'qr_code.max' => 'El campo QR code no debe exceder los 255 caracteres.',
            'qr_code.unique' => 'El QR code ya está en uso.',
            'estado.required' => 'El campo estado es obligatorio.',
            'estado.in' => 'El campo estado debe ser uno de los siguientes valores: disponible, ocupada, mantenimiento, inactiva.'
        ];

        try {
            $validation = $request->validate($rules, $messages);

            $aula = aulas::find($id);

            if (!$aula) {
                return response()->json([
                    'message' => 'Aula no encontrada',
                    'success' => false
                ], 404);
            }

            DB::beginTransaction();

            $aula->update($validation);

            DB::commit();

            return response()->json([
                'message' => 'Aula actualizada exitosamente',
                'success' => true,
                'data' => $aula
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el aula',
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
        // Solo permitir para Administrador académico
        if ($user_rol != 2) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $aula = aulas::where('id', $id)->lockForUpdate()->first();

            if (!$aula) {
                return response()->json([
                    'message' => 'Aula no encontrada',
                    'success' => false
                ], 404);
            }

            DB::beginTransaction();

            $aula->delete();

            DB::commit();

            return response()->json([
                'message' => 'Aula eliminada exitosamente',
                'success' => true
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el aula',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getClassroomByCode($codigo): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $codigo = $this->sanitizeInput($codigo);

        $aula = (new aulas())->getAulasByCode($codigo);

        if (!$aula || $aula->isEmpty()) {
            return response()->json([
                'message' => 'Aula no encontrada',
                'success' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Aula obtenida exitosamente',
            'success' => true,
            'data' => $aula
        ], 200);
    }

    public function getClassroomsByStatus($estado): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $valid_states = ['disponible', 'ocupada', 'mantenimiento', 'inactiva'];

            if (!in_array($estado, $valid_states)) {
                return response()->json([
                    'message' => 'Estado inválido. Los estados válidos son: disponible, ocupada, mantenimiento, inactiva.',
                    'success' => false
                ], 400);
            }

            $estado = $this->sanitizeInput($estado);

            $aulas = aulas::where('estado', $estado)->get();

            if ($aulas->isEmpty()) {
                return response()->json([
                    'message' => 'No hay aulas con el estado especificado',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Aulas obtenidas exitosamente',
                'success' => true,
                'data' => $aulas
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las aulas',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getClassroomsByMinCapacity($capacidad): JsonResponse {
        try {
            $capacidad = (int)$capacidad;

            if ($capacidad < 1) {
                return response()->json([
                    'message' => 'La capacidad mínima debe ser un número entero positivo.',
                    'success' => false
                ], 400);
            }

            $aulas = aulas::where('capacidad_pupitres', '>=', $capacidad)->get();

            if ($aulas->isEmpty()) {
                return response()->json([
                    'message' => 'No hay aulas con la capacidad mínima especificada',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Aulas obtenidas exitosamente',
                'success' => true,
                'data' => $aulas
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las aulas',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAvailableClassrooms(): JsonResponse {
        try {
            $aulas = aulas::where('estado', 'disponible')->get();

            if ($aulas->isEmpty()) {
                return response()->json([
                    'message' => 'No hay aulas disponibles',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Aulas disponibles obtenidas exitosamente',
                'success' => true,
                'data' => $aulas
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las aulas disponibles',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getClassroomsByLocation($ubicacion): JsonResponse {
        try {
            $ubicacion = $this->sanitizeInput($ubicacion);

            $aulas = aulas::where('ubicacion', 'LIKE', DB::raw('CONCAT("%", ?, "%")'), [$ubicacion])->get();

            if ($aulas->isEmpty()) {
                return response()->json([
                    'message' => 'No hay aulas en la ubicación especificada',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Aulas obtenidas exitosamente',
                'success' => true,
                'data' => $aulas
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las aulas',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getClassroomByQrCode($qr_code): JsonResponse {
        try {
            $qr_code = $this->sanitizeInput($qr_code);

            $aula = aulas::where('qr_code', $qr_code)->first();

            if (!$aula) {
                return response()->json([
                    'message' => 'Aula no encontrada con el QR code especificado',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Aula obtenida exitosamente',
                'success' => true,
                'data' => $aula
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el aula',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function changeClassroomStatus(Request $request, $id): JsonResponse {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'message' => 'Acceso no autorizado',
                    'success' => false
                ], 401);
            }

            $user_rol = $this->getUserRole();
            // Solo permitir para Administrador académico
            if ($user_rol != 2) {
                return response()->json([
                    'message' => 'Acceso no autorizado',
                    'success' => false
                ], 401);
            }

            $request->merge([
                'estado' => $this->sanitizeInput($request->estado)
            ]);

            $rules = [
                'estado' => 'required|in:disponible,ocupada,mantenimiento,inactiva'
            ];

            $messages = [
                'estado.required' => 'El campo estado es obligatorio.',
                'estado.in' => 'El campo estado debe ser uno de los siguientes valores: disponible, ocupada, mantenimiento, inactiva.'
            ];

            $validation = $request->validate($rules, $messages);

            $aula = aulas::find($id);

            if (!$aula) {
                return response()->json([
                    'message' => 'Aula no encontrada',
                    'success' => false
                ], 404);
            }

            DB::beginTransaction();

            $aula->estado = $validation['estado'];
            $aula->save();

            DB::commit();

            return response()->json([
                'message' => 'Estado del aula actualizado exitosamente',
                'success' => true,
                'data' => $aula
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el estado del aula',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getClassroomStatistics(Request $request, $id): JsonResponse {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'message' => 'Acceso no autorizado',
                    'success' => false
                ], 401);
            }

            $user_rol = $this->getUserRole();
            // Permitir para Administrador académico
            if ($user_rol != 2) {
                return response()->json([
                    'message' => 'Acceso no autorizado',
                    'success' => false
                ], 401);
            }

            $aula = aulas::find($id);

            if (!$aula) {
                return response()->json([
                    'message' => 'Aula no encontrada',
                    'success' => false
                ], 404);
            }

            $request->merge([
                'fecha_inicio' => $this->sanitizeInput($request->fecha_inicio),
                'fecha_fin' => $this->sanitizeInput($request->fecha_fin)
            ]);

            $rules = [
                'fecha_inicio' => 'required|date|before_or_equal:fecha_fin',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio'
            ];

            $messages = [
                'fecha_inicio.required' => 'El campo fecha de inicio es obligatorio.',
                'fecha_inicio.date' => 'El campo fecha de inicio debe ser una fecha válida.',
                'fecha_inicio.before_or_equal' => 'La fecha de inicio debe ser anterior o igual a la fecha de fin.',
                'fecha_fin.required' => 'El campo fecha de fin es obligatorio.',
                'fecha_fin.date' => 'El campo fecha de fin debe ser una fecha válida.',
                'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.'
            ];

            $validation = $request->validate($rules, $messages);

            $estadisticas = DB::select('CALL sp_estadisticas_aula(?, ?, ?)', [
                $id,
                $validation['fecha_inicio'],
                $validation['fecha_fin']
            ]);

            return response()->json([
                'message' => 'Estadísticas del aula obtenidas exitosamente',
                'success' => true,
                'data' => $estadisticas
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las estadísticas del aula',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getClassroomSuggestions(Request $request): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $request->merge([
            'capacidad_minima' => (int)$request->capacidad_minima,
            'dia_semana' => $this->sanitizeInput($request->dia_semana),
            'hora_inicio' => $this->sanitizeInput($request->hora_inicio),
            'hora_fin' => $this->sanitizeInput($request->hora_fin)
        ]);

        $rules = [
            'capacidad_minima' => 'required|integer|min:1',
            'dia_semana' => 'required|in:Lunes,Martes,Miercoles,Jueves,Viernes,Sabado,Domingo',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio'
        ];

        $messages = [
            'capacidad_minima.required' => 'El campo capacidad mínima es obligatorio.',
            'capacidad_minima.integer' => 'El campo capacidad mínima debe ser un número entero.',
            'capacidad_minima.min' => 'El campo capacidad mínima debe ser al menos 1.',
            'dia_semana.required' => 'El campo día de la semana es obligatorio.',
            'dia_semana.in' => 'El campo día de la semana debe ser uno de los siguientes valores: Lunes, Martes, Miercoles, Jueves, Viernes, Sabado, Domingo.',
            'hora_inicio.required' => 'El campo hora de inicio es obligatorio.',
            'hora_inicio.date_format' => 'El campo hora de inicio debe tener el formato HH:MM (24 horas).',
            'hora_fin.required' => 'El campo hora de fin es obligatorio.',
            'hora_fin.date_format' => 'El campo hora de fin debe tener el formato HH:MM (24 horas).',
            'hora_fin.after' => 'El campo hora de fin debe ser una hora posterior a la hora de inicio.'
        ];

        try {
            $validation = $request->validate($rules, $messages);

            $sugerencias = (new aulas())->getAulasSugeridas(
                $validation['capacidad_minima'],
                $validation['dia_semana'],
                $validation['hora_inicio'],
                $validation['hora_fin']
            );

            if ($sugerencias->isEmpty()) {
                return response()->json([
                    'message' => 'No hay aulas que cumplan con los criterios especificados',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Sugerencias de aulas obtenidas exitosamente',
                'success' => true,
                'data' => $sugerencias
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las sugerencias de aulas',
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
