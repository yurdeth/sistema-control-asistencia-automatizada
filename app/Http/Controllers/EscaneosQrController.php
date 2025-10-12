<?php

namespace App\Http\Controllers;

use App\Http\Requests\Storeescaneos_qrRequest;
use App\Http\Requests\Updateescaneos_qrRequest;
use App\Models\escaneos_qr;

class EscaneosQrController extends Controller
{
    public function index()
    {
        try {
            $escaneos = escaneos_qr::with(['aula', 'usuario', 'sesionClase'])->get();
            return response()->json([
                'success' => true,
                'data' => $escaneos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los escaneos QR',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $escaneo = escaneos_qr::with(['aula', 'usuario', 'sesionClase'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $escaneo
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Escaneo no encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function registerScan(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'aula_id' => 'required|exists:aulas,id',
                'usuario_id' => 'required|exists:usuarios,id',
                'tipo_escaneo' => 'required|in:entrada_docente,salida_docente,asistencia_estudiante',
                'sesion_clase_id' => 'nullable|exists:sesiones_clase,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $escaneo = escaneos_qr::create([
                'aula_id' => $request->aula_id,
                'usuario_id' => $request->usuario_id,
                'sesion_clase_id' => $request->sesion_clase_id,
                'tipo_escaneo' => $request->tipo_escaneo,
                'resultado' => 'exitoso',
                'ip_address' => $request->ip(),
                'fecha_hora' => Carbon::now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Escaneo registrado exitosamente',
                'data' => $escaneo
            ], 201);
        } catch (\Exception $e) {
            // Registrar escaneo fallido
            escaneos_qr::create([
                'aula_id' => $request->aula_id ?? null,
                'usuario_id' => $request->usuario_id ?? null,
                'sesion_clase_id' => $request->sesion_clase_id ?? null,
                'tipo_escaneo' => $request->tipo_escaneo ?? 'entrada_docente',
                'resultado' => 'fallido',
                'motivo_fallo' => $e->getMessage(),
                'ip_address' => $request->ip(),
                'fecha_hora' => Carbon::now()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el escaneo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByClassroom($id)
    {
        try {
            $escaneos = escaneos_qr::with(['usuario', 'sesionClase'])
                ->where('aula_id', $id)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $escaneos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los escaneos del aula',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByUser($id)
    {
        try {
            $escaneos = escaneos_qr::with(['aula', 'sesionClase'])
                ->where('usuario_id', $id)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $escaneos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los escaneos del usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getBySession($id)
    {
        try {
            $escaneos = escaneos_qr::with(['aula', 'usuario'])
                ->where('sesion_clase_id', $id)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $escaneos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los escaneos de la sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByType($tipo)
    {
        try {
            $escaneos = escaneos_qr::with(['aula', 'usuario', 'sesionClase'])
                ->where('tipo_escaneo', $tipo)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $escaneos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los escaneos por tipo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByResult($resultado)
    {
        try {
            $escaneos = escaneos_qr::with(['aula', 'usuario', 'sesionClase'])
                ->where('resultado', $resultado)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $escaneos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los escaneos por resultado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getRecentFailed()
    {
        try {
            $escaneos = escaneos_qr::with(['aula', 'usuario'])
                ->where('resultado', 'fallido')
                ->orderBy('fecha_hora', 'desc')
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $escaneos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los escaneos fallidos recientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByDateRange(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after:fecha_inicio',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $escaneos = escaneos_qr::with(['aula', 'usuario', 'sesionClase'])
                ->whereBetween('fecha_hora', [$request->fecha_inicio, $request->fecha_fin])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $escaneos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los escaneos por rango de fecha',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
