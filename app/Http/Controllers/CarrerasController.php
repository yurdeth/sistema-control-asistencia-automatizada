<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CarrerasController extends Controller {
    /**
     * Obtener todas las carreras
     */
    public function index(): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $carreras = DB::table('carreras')
                ->select('id', 'nombre', 'departamento_id')
                ->orderBy('nombre', 'asc')
                ->get();

            return response()->json([
                'message' => 'Carreras obtenidas exitosamente',
                'success' => true,
                'data' => $carreras
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las carreras',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener carreras por departamento
     */
    public function getByDepartamento($departamentoId): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $carreras = DB::table('carreras')
                ->select('id', 'nombre', 'departamento_id')
                ->where('departamento_id', $departamentoId)
                ->orderBy('nombre', 'asc')
                ->get();

            return response()->json([
                'message' => 'Carreras obtenidas exitosamente',
                'success' => true,
                'data' => $carreras
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las carreras',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
