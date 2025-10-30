<?php

namespace App\Http\Controllers;

use App\Models\estadisticas_aulas_diarias;
use App\RolesEnum;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EstadisticasAulasDiariasController extends Controller {
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
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $estadisticas = estadisticas_aulas_diarias::with(['aula'])->get();

            if ($estadisticas->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron estadísticas de aulas'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $estadisticas
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas de aulas',
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

        try {
            $estadistica = estadisticas_aulas_diarias::with(['aula'])->find($id);

            if (!$estadistica) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estadística no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $estadistica
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la estadística',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByClassroom($id): JsonResponse {
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

        try {
            $estadisticas = estadisticas_aulas_diarias::where('aula_id', $id)
                ->orderBy('fecha', 'desc')
                ->get();

            if ($estadisticas->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron estadísticas para esta aula'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $estadisticas
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas del aula',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByDate($fecha): JsonResponse {
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

        try {
            $estadisticas = estadisticas_aulas_diarias::with(['aula'])
                ->where('fecha', $fecha)
                ->get();

            if ($estadisticas->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron estadísticas para esta fecha'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $estadisticas
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas por fecha',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByDateRange(Request $request): JsonResponse {
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

            $estadisticas = estadisticas_aulas_diarias::with(['aula'])
                ->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin])
                ->orderBy('fecha', 'desc')
                ->get();

            if ($estadisticas->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron estadísticas en el rango de fechas especificado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $estadisticas
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas por rango de fecha',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getTopOccupied(Request $request): JsonResponse {
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

        try {
            $limit = $request->input('limit', 10);

            $estadisticas = estadisticas_aulas_diarias::with(['aula'])
                ->select('aula_id', DB::raw('AVG(porcentaje_ocupacion) as promedio_ocupacion'))
                ->groupBy('aula_id')
                ->orderBy('promedio_ocupacion', 'desc')
                ->limit($limit)
                ->get();

            if ($estadisticas->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron estadísticas'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $estadisticas
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las aulas más ocupadas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getLowOccupancy(Request $request): JsonResponse {
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

        try {
            $umbral = $request->input('umbral', 30); // 30% por defecto

            $estadisticas = estadisticas_aulas_diarias::with(['aula'])
                ->where('porcentaje_ocupacion', '<', $umbral)
                ->orderBy('fecha', 'desc')
                ->get();

            if ($estadisticas->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "No se encontraron aulas con ocupación menor al {$umbral}%"
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $estadisticas
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las aulas con baja ocupación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAverageOccupancy(): JsonResponse {
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

        try {
            $promedios = estadisticas_aulas_diarias::with(['aula'])
                ->select('aula_id',
                    DB::raw('AVG(porcentaje_ocupacion) as promedio_ocupacion'),
                    DB::raw('AVG(minutos_ocupada) as promedio_minutos'))
                ->groupBy('aula_id')
                ->get();

            if ($promedios->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron estadísticas'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $promedios
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al calcular el promedio de ocupación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getTotalMinutesByClassroom($id): JsonResponse {
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

        try {
            $total = estadisticas_aulas_diarias::where('aula_id', $id)
                ->sum('minutos_ocupada');

            return response()->json([
                'success' => true,
                'aula_id' => $id,
                'total_minutos_ocupada' => $total,
                'total_horas_ocupada' => round($total / 60, 2)
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al calcular el total de minutos',
                'error' => $e->getMessage()
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
