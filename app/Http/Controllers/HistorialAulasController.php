<?php

namespace App\Http\Controllers;

use App\Models\historial_aulas;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HistorialAulasController extends Controller {
    public function index(): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $historial = historial_aulas::with(['aula', 'usuarioModificacion'])->get();

            if ($historial->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron registros en el historial de aulas'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $historial
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial de aulas',
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

        try {
            $registro = historial_aulas::with(['aula', 'usuarioModificacion'])->find($id);

            if (!$registro) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registro no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $registro
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el registro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByClassroom($id): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        if ($user_rol > 5) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $historial = historial_aulas::with(['usuarioModificacion'])
                ->where('aula_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($historial->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró historial para esta aula'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $historial
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial del aula',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByUser($id): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        if ($user_rol > 5) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $historial = historial_aulas::with(['aula'])
                ->where('usuario_modificacion_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($historial->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron modificaciones realizadas por este usuario'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $historial
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial del usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByOperation($tipo): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        if ($user_rol > 5) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $historial = historial_aulas::with(['aula', 'usuarioModificacion'])
                ->where('tipo_operacion', $tipo)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($historial->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "No se encontraron registros de tipo: {$tipo}"
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $historial
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial por tipo de operación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByField($campo): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        if ($user_rol > 5) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $historial = historial_aulas::with(['aula', 'usuarioModificacion'])
                ->where('campo_modificado', $campo)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($historial->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "No se encontraron modificaciones del campo: {$campo}"
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $historial
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial por campo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getRecent(): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        if ($user_rol > 5) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $historial = historial_aulas::with(['aula', 'usuarioModificacion'])
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            if ($historial->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron registros recientes'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $historial
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los registros recientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByDateRange(Request $request): JsonResponse {
        $user_rol = $this->getUserRole();

        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        if ($user_rol > 5) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $validator = Validator::make($request->all(), [
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $historial = historial_aulas::with(['aula', 'usuarioModificacion'])
                ->whereBetween('created_at', [$request->fecha_inicio, $request->fecha_fin])
                ->orderBy('created_at', 'desc')
                ->get();

            if ($historial->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron registros en el rango de fechas especificado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $historial
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial por rango de fecha',
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