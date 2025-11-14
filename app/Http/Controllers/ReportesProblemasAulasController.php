<?php

namespace App\Http\Controllers;

use App\Models\ReporteProblemaAula;
use App\Models\aulas;
use App\RolesEnum;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ReportesProblemasAulasController extends Controller
{
    /**
     * Crear un nuevo reporte de problema en aula
     * Accesible por: Docentes y Estudiantes (cualquier usuario autenticado)
     */
    public function store(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $rules = [
            'aula_id' => 'required|exists:aulas,id',
            'categoria' => 'required|in:recurso_defectuoso,qr_danado,limpieza,infraestructura,conectividad,otro',
            'descripcion' => 'required|string|min:10|max:1000',
            'foto_evidencia' => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // 5MB máximo
        ];

        $messages = [
            'aula_id.required' => 'El ID del aula es requerido',
            'aula_id.exists' => 'El aula especificada no existe',
            'categoria.required' => 'La categoría del problema es requerida',
            'categoria.in' => 'La categoría debe ser: recurso_defectuoso, qr_danado, limpieza, infraestructura, conectividad u otro',
            'descripcion.required' => 'La descripción del problema es requerida',
            'descripcion.min' => 'La descripción debe tener al menos 10 caracteres',
            'descripcion.max' => 'La descripción no puede exceder 1000 caracteres',
            'foto_evidencia.image' => 'El archivo debe ser una imagen',
            'foto_evidencia.mimes' => 'La imagen debe ser formato: jpeg, png o jpg',
            'foto_evidencia.max' => 'La imagen no puede pesar más de 5MB',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = [
                'aula_id' => $request->aula_id,
                'usuario_reporta_id' => Auth::id(),
                'categoria' => $request->categoria,
                'descripcion' => $request->descripcion,
                'estado' => 'reportado',
            ];

            // Procesar foto de evidencia si existe
            if ($request->hasFile('foto_evidencia')) {
                $foto = $request->file('foto_evidencia');
                $nombreArchivo = 'reporte_' . time() . '_' . uniqid() . '.' . $foto->getClientOriginalExtension();
                $ruta = $foto->storeAs('reportes_problemas', $nombreArchivo, 'public');
                $data['foto_evidencia'] = $ruta;
            }

            $reporte = ReporteProblemaAula::create($data);

            // Cargar relaciones para la respuesta
            $reporte->load(['aula', 'usuarioReporta']);

            return response()->json([
                'success' => true,
                'message' => 'Reporte de problema creado exitosamente',
                'data' => $reporte
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el reporte de problema',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar todos los reportes
     * Accesible por: Administradores y Gestores
     */
    public function index(): JsonResponse
    {
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
            $reportes = ReporteProblemaAula::with(['aula', 'usuarioReporta', 'usuarioAsignado'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $reportes
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los reportes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ver un reporte específico
     */
    public function show($id): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $reporte = ReporteProblemaAula::with(['aula', 'usuarioReporta', 'usuarioAsignado'])->find($id);

            if (!$reporte) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reporte no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $reporte
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el reporte',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener reportes por aula
     */
    public function getByAula($aula_id): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $reportes = ReporteProblemaAula::with(['usuarioReporta', 'usuarioAsignado'])
                ->where('aula_id', $aula_id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $reportes
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los reportes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener reportes por estado
     */
    public function getByEstado($estado): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $estadosValidos = ['reportado', 'en_revision', 'asignado', 'en_proceso', 'resuelto', 'rechazado', 'cerrado'];

        if (!in_array($estado, $estadosValidos)) {
            return response()->json([
                'success' => false,
                'message' => 'Estado no válido'
            ], 422);
        }

        try {
            $reportes = ReporteProblemaAula::with(['aula', 'usuarioReporta', 'usuarioAsignado'])
                ->where('estado', $estado)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $reportes
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los reportes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener reportes por categoría
     */
    public function getByCategoria($categoria): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $categoriasValidas = ['recurso_defectuoso', 'qr_danado', 'limpieza', 'infraestructura', 'conectividad', 'otro'];

        if (!in_array($categoria, $categoriasValidas)) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no válida'
            ], 422);
        }

        try {
            $reportes = ReporteProblemaAula::with(['aula', 'usuarioReporta', 'usuarioAsignado'])
                ->where('categoria', $categoria)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $reportes
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los reportes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener los reportes creados por el usuario autenticado
     */
    public function getMyReports(): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            $reportes = ReporteProblemaAula::with(['aula', 'usuarioAsignado'])
                ->where('usuario_reporta_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $reportes
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tus reportes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar el estado de un reporte
     */
    public function cambiarEstado(Request $request, $id): JsonResponse
    {
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

        $rules = [
            'estado' => 'required|in:reportado,en_revision,asignado,en_proceso,resuelto,rechazado,cerrado',
        ];

        $messages = [
            'estado.required' => 'El estado es requerido',
            'estado.in' => 'El estado debe ser: reportado, en_revision, asignado, en_proceso, resuelto, rechazado o cerrado',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $reporte = ReporteProblemaAula::find($id);

            if (!$reporte) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reporte no encontrado'
                ], 404);
            }

            $reporte->estado = $request->estado;

            // Si se marca como resuelto y no tiene fecha de resolución, agregarla
            if ($request->estado === 'resuelto' && !$reporte->fecha_resolucion) {
                $reporte->fecha_resolucion = Carbon::now();
            }

            $reporte->save();

            $reporte->load(['aula', 'usuarioReporta', 'usuarioAsignado']);

            return response()->json([
                'success' => true,
                'message' => 'Estado del reporte actualizado exitosamente',
                'data' => $reporte
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Asignar un usuario a un reporte
     */
    public function asignarUsuario(Request $request, $id): JsonResponse
    {
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

        $rules = [
            'usuario_asignado_id' => 'required|exists:users,id',
        ];

        $messages = [
            'usuario_asignado_id.required' => 'El ID del usuario es requerido',
            'usuario_asignado_id.exists' => 'El usuario especificado no existe',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $reporte = ReporteProblemaAula::find($id);

            if (!$reporte) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reporte no encontrado'
                ], 404);
            }

            $reporte->usuario_asignado_id = $request->usuario_asignado_id;

            // Cambiar estado a 'asignado' automáticamente
            if ($reporte->estado === 'reportado' || $reporte->estado === 'en_revision') {
                $reporte->estado = 'asignado';
            }

            $reporte->save();

            $reporte->load(['aula', 'usuarioReporta', 'usuarioAsignado']);

            return response()->json([
                'success' => true,
                'message' => 'Usuario asignado exitosamente al reporte',
                'data' => $reporte
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar un reporte como resuelto
     */
    public function marcarResuelto(Request $request, $id): JsonResponse
    {
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

        $rules = [
            'notas_resolucion' => 'nullable|string|max:1000',
        ];

        $messages = [
            'notas_resolucion.max' => 'Las notas de resolución no pueden exceder 1000 caracteres',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $reporte = ReporteProblemaAula::find($id);

            if (!$reporte) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reporte no encontrado'
                ], 404);
            }

            $reporte->estado = 'resuelto';
            $reporte->fecha_resolucion = Carbon::now();

            if ($request->has('notas_resolucion')) {
                $reporte->notas_resolucion = $request->notas_resolucion;
            }

            $reporte->save();

            $reporte->load(['aula', 'usuarioReporta', 'usuarioAsignado']);

            return response()->json([
                'success' => true,
                'message' => 'Reporte marcado como resuelto exitosamente',
                'data' => $reporte
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar el reporte como resuelto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener lista de categorías disponibles
     */
    public function getCategorias(): JsonResponse
    {
        $categorias = [
            [
                'value' => 'recurso_defectuoso',
                'label' => 'Recurso Defectuoso',
                'descripcion' => 'Problemas con recursos del aula (proyector, aire acondicionado, pizarra, etc.)'
            ],
            [
                'value' => 'qr_danado',
                'label' => 'Código QR Dañado',
                'descripcion' => 'Código QR ilegible, rayado o dañado'
            ],
            [
                'value' => 'limpieza',
                'label' => 'Limpieza',
                'descripcion' => 'Problemas de limpieza en el aula'
            ],
            [
                'value' => 'infraestructura',
                'label' => 'Infraestructura',
                'descripcion' => 'Daños en infraestructura (puertas, ventanas, paredes, etc.)'
            ],
            [
                'value' => 'conectividad',
                'label' => 'Conectividad',
                'descripcion' => 'Problemas de internet o conectividad'
            ],
            [
                'value' => 'otro',
                'label' => 'Otro',
                'descripcion' => 'Otros problemas no categorizados'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $categorias
        ], 200);
    }

    /**
     * Obtener lista de estados disponibles
     */
    public function getEstados(): JsonResponse
    {
        $estados = [
            [
                'value' => 'reportado',
                'label' => 'Reportado',
                'descripcion' => 'Reporte recién creado',
                'color' => '#3B82F6' // Azul
            ],
            [
                'value' => 'en_revision',
                'label' => 'En Revisión',
                'descripcion' => 'El reporte está siendo revisado',
                'color' => '#F59E0B' // Amarillo/Naranja
            ],
            [
                'value' => 'asignado',
                'label' => 'Asignado',
                'descripcion' => 'Se asignó un responsable',
                'color' => '#8B5CF6' // Púrpura
            ],
            [
                'value' => 'en_proceso',
                'label' => 'En Proceso',
                'descripcion' => 'El problema está siendo atendido',
                'color' => '#06B6D4' // Cyan
            ],
            [
                'value' => 'resuelto',
                'label' => 'Resuelto',
                'descripcion' => 'El problema fue solucionado',
                'color' => '#10B981' // Verde
            ],
            [
                'value' => 'rechazado',
                'label' => 'Rechazado',
                'descripcion' => 'El reporte fue rechazado',
                'color' => '#EF4444' // Rojo
            ],
            [
                'value' => 'cerrado',
                'label' => 'Cerrado',
                'descripcion' => 'El reporte fue cerrado',
                'color' => '#6B7280' // Gris
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $estados
        ], 200);
    }

    /**
     * Obtener estadísticas generales de reportes
     * Accesible por: Administradores y Gestores
     */
    public function getEstadisticas(): JsonResponse
    {
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
            // Conteo por estado
            $porEstado = ReporteProblemaAula::selectRaw('estado, COUNT(*) as total')
                ->groupBy('estado')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->estado => $item->total];
                });

            // Conteo por categoría
            $porCategoria = ReporteProblemaAula::selectRaw('categoria, COUNT(*) as total')
                ->groupBy('categoria')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->categoria => $item->total];
                });

            // Total de reportes
            $totalReportes = ReporteProblemaAula::count();

            // Reportes del mes actual
            $reportesMesActual = ReporteProblemaAula::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count();

            // Reportes pendientes (no resueltos ni cerrados)
            $reportesPendientes = ReporteProblemaAula::whereNotIn('estado', ['resuelto', 'cerrado', 'rechazado'])
                ->count();

            // Reportes resueltos
            $reportesResueltos = ReporteProblemaAula::where('estado', 'resuelto')
                ->count();

            // Top 5 aulas con más reportes
            $topAulas = ReporteProblemaAula::with('aula')
                ->selectRaw('aula_id, COUNT(*) as total')
                ->groupBy('aula_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'aula_id' => $item->aula_id,
                        'aula_nombre' => $item->aula->nombre ?? 'N/A',
                        'total_reportes' => $item->total
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'total_reportes' => $totalReportes,
                    'reportes_mes_actual' => $reportesMesActual,
                    'reportes_pendientes' => $reportesPendientes,
                    'reportes_resueltos' => $reportesResueltos,
                    'por_estado' => $porEstado,
                    'por_categoria' => $porCategoria,
                    'top_aulas_con_reportes' => $topAulas
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener el nombre del rol del usuario autenticado
     */
    private function getUserRoleName(): string|null
    {
        return DB::table('usuario_roles')
            ->join('users', 'usuario_roles.usuario_id', '=', 'users.id')
            ->join('roles', 'usuario_roles.rol_id', '=', 'roles.id')
            ->where('users.id', Auth::id())
            ->value('roles.nombre');
    }
}
