<?php

namespace App\Http\Controllers;

use App\Models\escaneos_qr;
use App\RolesEnum;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EscaneosQrController extends Controller {
    public function index(): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $escaneos = escaneos_qr::with(['aula', 'usuario', 'sesionClase'])->get();

            if ($escaneos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron escaneos QR'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $escaneos
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los escaneos QR',
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
            $escaneo = escaneos_qr::with(['aula', 'usuario', 'sesionClase'])->find($id);

            if (!$escaneo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Escaneo no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $escaneo
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el escaneo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function registerScan(Request $request): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $request->merge([
            'aula_id' => $this->sanitizeInput($request->input('aula_id')),
            'usuario_id' => $this->sanitizeInput($request->input('usuario_id')),
            'tipo_escaneo' => $this->sanitizeInput($request->input('tipo_escaneo')),
            'sesion_clase_id' => $this->sanitizeInput($request->input('sesion_clase_id')),
        ]);

        $rules = [
            'aula_id' => 'required|exists:aulas,id',
            'usuario_id' => 'required|exists:users,id',
            'tipo_escaneo' => 'required|in:entrada_docente,salida_docente,asistencia_estudiante',
            'sesion_clase_id' => 'nullable|exists:sesiones_clases,id',
        ];

        $messages = [
            'aula_id.required' => 'El ID del aula es obligatorio.',
            'aula_id.exists' => 'El aula especificada no existe.',
            'usuario_id.required' => 'El ID del usuario es obligatorio.',
            'usuario_id.exists' => 'El usuario especificado no existe.',
            'tipo_escaneo.required' => 'El tipo de escaneo es obligatorio.',
            'tipo_escaneo.in' => 'El tipo de escaneo debe ser uno de los siguientes: entrada_docente, salida_docente, asistencia_estudiante.',
            'sesion_clase_id.exists' => 'La sesión de clase especificada no existe.',
        ];

        try {
            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $escaneo = escaneos_qr::create([
                'aula_id' => $request->aula_id,
                'usuario_id' => $request->usuario_id,
                'sesion_clase_id' => $request->sesion_clase_id,
                'tipo_escaneo' => $request->tipo_escaneo,
                // El enum en la BD acepta: 'exito', 'fallo', 'no_autorizado'
                'resultado' => 'exito',
                'ip_address' => $request->ip()
                // created_at se crea automáticamente
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Escaneo registrado exitosamente',
                'data' => $escaneo
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            // Registrar escaneo fallido
            escaneos_qr::create([
                'aula_id' => $request->aula_id ?? null,
                'usuario_id' => $request->usuario_id ?? null,
                'sesion_clase_id' => $request->sesion_clase_id ?? null,
                'tipo_escaneo' => $request->tipo_escaneo ?? 'entrada_docente',
                'resultado' => 'fallo',
                'motivo_fallo' => $e->getMessage(),
                'ip_address' => $request->ip()
                // created_at se crea automáticamente
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el escaneo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByClassroom($id): JsonResponse {
        // Esta mierda es para validar los roles y autorizar el acceso; no borrar
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
            RolesEnum::DOCENTE->value,
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $escaneos = escaneos_qr::with(['usuario', 'sesionClase'])
                ->where('aula_id', $id)
                ->get();

            if ($escaneos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron escaneos para esta aula'
                ], 404);
            }

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

    public function getByUser($id): JsonResponse {
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
            RolesEnum::DOCENTE->value,
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $escaneos = escaneos_qr::with(['aula', 'sesionClase'])
                ->where('usuario_id', $id)
                ->get();

            if ($escaneos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron escaneos para este usuario'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $escaneos
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los escaneos del usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getBySession($id): JsonResponse {
        $user_rol = $this->getUserRoleId();

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
            RolesEnum::DOCENTE->value,
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $escaneos = escaneos_qr::with(['aula', 'usuario'])
                ->where('sesion_clase_id', $id)
                ->get();

            if ($escaneos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron escaneos para esta sesión'
                ], 404);
            }

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

    public function getByType($tipo): JsonResponse {
        $user_rol = $this->getUserRoleId();

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
            RolesEnum::DOCENTE->value,
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $escaneos = escaneos_qr::with(['aula', 'usuario', 'sesionClase'])
                ->where('tipo_escaneo', $tipo)
                ->get();

            if ($escaneos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "No se encontraron escaneos de tipo: {$tipo}"
                ], 404);
            }

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

    public function getByResult($resultado): JsonResponse {
        $user_rol = $this->getUserRoleId();

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
            RolesEnum::DOCENTE->value,
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $escaneos = escaneos_qr::with(['aula', 'usuario', 'sesionClase'])
                ->where('resultado', $resultado)
                ->get();

            if ($escaneos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "No se encontraron escaneos con resultado: {$resultado}"
                ], 404);
            }

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

    public function getRecentFailed(): JsonResponse {
        $user_rol = $this->getUserRoleId();

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
            RolesEnum::DOCENTE->value,
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        try {
            $escaneos = escaneos_qr::with(['aula', 'usuario'])
                ->where('resultado', 'fallo')
                ->orderBy('created_at', 'desc')  // Usar created_at en lugar de fecha_hora
                ->limit(50)
                ->get();

            if ($escaneos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron escaneos fallidos recientes'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $escaneos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los escaneos fallos recientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByDateRange(Request $request): JsonResponse {
        $user_rol = $this->getUserRoleId();

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
            RolesEnum::DOCENTE->value,
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
                ->whereBetween('created_at', [$request->fecha_inicio, $request->fecha_fin])  // Usar created_at
                ->get();

            if ($escaneos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron escaneos en el rango de fechas especificado'
                ], 404);
            }

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

    private function getUserRoleId() {
        return DB::table('usuario_roles')
            ->join('users', 'usuario_roles.usuario_id', '=', 'users.id')
            ->where('users.id', Auth::id())
            ->value('usuario_roles.rol_id');
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
