<?php

namespace App\Http\Controllers;

use App\Http\Concerns\ValidatesRoles;
use App\Models\AulaFoto;
use App\Models\aulas;
use App\Models\AulaVideo;
use App\RolesEnum;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache as FacadesCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Attribute\Cache;

class AulasController extends Controller {
    // Temporalmente comentado para resolver el error
    // use ValidatesRoles;
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
                    'created_at' => $aula->created_at,
                    'updated_at' => $aula->updated_at,
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

        // Agregar fotos y videos con URLs completas
        $storage_url = config('storage.url.img');
        foreach ($aulas as &$aula) {
            $aula_model = aulas::find($aula['id']);

            // Agregar indicaciones y coordenadas
            $aula['indicaciones'] = $aula_model->indicaciones;
            $aula['latitud'] = $aula_model->latitud;
            $aula['longitud'] = $aula_model->longitud;

            // Agregar fotos
            $aula['fotos'] = $aula_model->fotos->map(function ($foto) use ($storage_url) {
                return [
                    'id' => $foto->id,
                    'url' => $storage_url . '/' . $foto->ruta
                ];
            })->toArray();

            // Agregar videos
            $aula['videos'] = $aula_model->videos->map(function ($video) {
                return [
                    'id' => $video->id,
                    'url' => $video->url
                ];
            })->toArray();
        }

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

        $aulas = (new aulas())->getAllById($id);

        if (!$aulas || $aulas->isEmpty()) {
            return response()->json([
                'message' => 'Aula no encontrada',
                'success' => false
            ], 404);
        }

        // Crear array recursos, agrupando por aula_id
        $aula_data = null;
        foreach ($aulas as $aula) {
            if (!$aula_data) {
                $aula_data = [
                    'id' => $aula->aula_id,
                    'codigo' => $aula->codigo_aula,
                    'nombre' => $aula->nombre_aula,
                    'capacidad_pupitres' => $aula->capacidad_pupitres,
                    'ubicacion' => $aula->ubicacion_aula,
                    'qr_code' => $aula->qr_code,
                    'estado' => $aula->estado_aula,
                    'created_at' => $aula->created_at,
                    'updated_at' => $aula->updated_at,
                    'recursos' => []
                ];
            }
            if ($aula->recurso_tipo_nombre) {
                $aula_data['recursos'][] = [
                    'nombre' => $aula->recurso_tipo_nombre,
                    'cantidad' => $aula->recurso_cantidad,
                    'estado' => $aula->estado_recurso,
                    'observaciones_recurso' => $aula->observaciones_recurso,
                    'aula_recurso_id' => $aula->aula_id
                ];
            }
        }

        // Agregar fotos y videos con URLs completas
        $aula_model = aulas::find($id);
        $storage_url = env('APP_URL') . '/storage';

        $aula_data['indicaciones'] = $aula_model->indicaciones;
        $aula_data['latitud'] = $aula_model->latitud;
        $aula_data['longitud'] = $aula_model->longitud;

        $aula_data['fotos'] = $aula_model->fotos->map(function ($foto) use ($storage_url) {
            return [
                'id' => $foto->id,
                'url' => $storage_url . '/' . $foto->ruta
            ];
        })->toArray();

        $aula_data['videos'] = $aula_model->videos->map(function ($video) {
            return [
                'id' => $video->id,
                'url' => $video->url
            ];
        })->toArray();

        return response()->json([
            'message' => 'Aula obtenida exitosamente',
            'success' => true,
            'data' => $aula_data
        ], 200);
    }

    public function edit(Request $request, $id): JsonResponse {
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
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        $request->merge([
            'codigo' => $this->sanitizeInput($request->codigo),
            'nombre' => $this->sanitizeInput($request->nombre),
            'capacidad_pupitres' => (int)$request->capacidad_pupitres,
            'ubicacion' => $this->sanitizeInput($request->ubicacion),
            'qr_code' => $this->sanitizeInput($request->qr_code),
            'estado' => $this->sanitizeInput($request->estado),
            'indicaciones' => $request->indicaciones ? $this->sanitizeInput($request->indicaciones) : null,
            'latitud' => $request->latitud,
            'longitud' => $request->longitud,
        ]);

        $rules = [
            'codigo' => 'required|string|max:50|unique:aulas,codigo,' . $id,
            'nombre' => 'required|string|max:100',
            'capacidad_pupitres' => 'required|integer|min:1',
            'ubicacion' => 'required|string',
            'indicaciones' => 'nullable|string',
            'latitud' => 'nullable|numeric|between:-90,90',
            'longitud' => 'nullable|numeric|between:-180,180',
            'qr_code' => 'nullable|string|max:255|unique:aulas,qr_code,' . $id,
            'estado' => 'required|in:disponible,ocupada,mantenimiento,inactiva',
            'fotos.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'videos.*' => 'nullable|url'
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
            'indicaciones.string' => 'El campo indicaciones debe ser una cadena de texto.',
            'latitud.numeric' => 'El campo latitud debe ser un número.',
            'latitud.between' => 'El campo latitud debe estar entre -90 y 90.',
            'longitud.numeric' => 'El campo longitud debe ser un número.',
            'longitud.between' => 'El campo longitud debe estar entre -180 y 180.',
            'qr_code.string' => 'El campo QR code debe ser una cadena de texto.',
            'qr_code.max' => 'El campo QR code no debe exceder los 255 caracteres.',
            'qr_code.unique' => 'El QR code ya está en uso.',
            'estado.required' => 'El campo estado es obligatorio.',
            'estado.in' => 'El campo estado debe ser uno de los siguientes valores: disponible, ocupada, mantenimiento, inactiva.',
            'fotos.*.image' => 'Cada archivo debe ser una imagen.',
            'fotos.*.mimes' => 'Las imágenes deben ser de tipo: jpeg, png, jpg, webp.',
            'fotos.*.max' => 'Cada imagen no debe exceder los 5MB.',
            'videos.*.url' => 'Cada video debe ser una URL válida.'
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

            // Guardar nuevas fotos si existen
            if ($request->hasFile('fotos')) {
                foreach ($request->file('fotos') as $foto) {
                    $ruta = $foto->store('aulas', 'public');
                    AulaFoto::create([
                        'aula_id' => $aula->id,
                        'ruta' => $ruta
                    ]);
                }
            }

            // Guardar nuevos videos si existen
            if ($request->has('videos')) {
                foreach ($request->videos as $video_url) {
                    if (!empty($video_url)) {
                        AulaVideo::create([
                            'aula_id' => $aula->id,
                            'url' => $video_url
                        ]);
                    }
                }
            }

            DB::commit();

            // Cargar relaciones para la respuesta
            $aula->load('fotos', 'videos');
            FacadesCache::clear();

            return response()->json([
                'message' => 'Aula actualizada exitosamente',
                'success' => true,
                'data' => $aula
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al actualizar el aula',
                'success' => false,
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

    public function store(Request $request): JsonResponse {
        Log::info($request);
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
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        $request->merge([
            'codigo' => $this->sanitizeInput($request->codigo),
            'nombre' => $this->sanitizeInput($request->nombre),
            'capacidad_pupitres' => (int)$request->capacidad_pupitres,
            'ubicacion' => $this->sanitizeInput($request->ubicacion),
            'estado' => $this->sanitizeInput($request->estado),
            'indicaciones' => $request->indicaciones ? $this->sanitizeInput($request->indicaciones) : null,
            'latitud' => $request->latitud,
            'longitud' => $request->longitud,
        ]);

        $rules = [
            'codigo' => 'required|string|max:50|unique:aulas,codigo',
            'nombre' => 'required|string|max:100',
            'capacidad_pupitres' => 'required|integer|min:1',
            'ubicacion' => 'required|string',
            'indicaciones' => 'nullable|string',
            'latitud' => 'nullable|numeric|between:-90,90',
            'longitud' => 'nullable|numeric|between:-180,180',
            'estado' => 'required|in:disponible,ocupada,mantenimiento,inactiva',
            'fotos.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'videos.*' => 'nullable|url'
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
            'indicaciones.string' => 'El campo indicaciones debe ser una cadena de texto.',
            'latitud.numeric' => 'El campo latitud debe ser un número.',
            'latitud.between' => 'El campo latitud debe estar entre -90 y 90.',
            'longitud.numeric' => 'El campo longitud debe ser un número.',
            'longitud.between' => 'El campo longitud debe estar entre -180 y 180.',
            'estado.required' => 'El campo estado es obligatorio.',
            'estado.in' => 'El campo estado debe ser uno de los siguientes valores: disponible, ocupada, mantenimiento, inactiva.',
            'fotos.*.image' => 'Cada archivo debe ser una imagen.',
            'fotos.*.mimes' => 'Las imágenes deben ser de tipo: jpeg, png, jpg, webp.',
            'fotos.*.max' => 'Cada imagen no debe exceder los 5MB.',
            'videos.*.url' => 'Cada video debe ser una URL válida.'
        ];

        try {
            $validation = $request->validate($rules, $messages);

            DB::beginTransaction();

            //genera el uuid
            $validation['qr_code'] = Str::uuid()->toString();

            $aula = aulas::create($validation);

            // Guardar fotos si existen
            if ($request->hasFile('fotos')) {
                $fotos = $request->file('fotos');

                // Si es un solo archivo, convertirlo en array
                if (!is_array($fotos)) {
                    $fotos = [$fotos];
                }

                foreach ($fotos as $foto) {
                    $ruta = $foto->store('aulas', 'public');
                    AulaFoto::create([
                        'aula_id' => $aula->id,
                        'ruta' => $ruta
                    ]);
                }
            }

            // Guardar videos si existen
            if ($request->has('videos') && !empty($request->videos)) {
                $videos = $request->videos;

                // Si es un string (una sola URL), convertirlo en array
                if (is_string($videos)) {
                    $videos = array_filter([$videos]); // Filtra vacíos
                }

                // Si ya es array, filtrar vacíos
                if (is_array($videos)) {
                    $videos = array_filter($videos);
                }

                foreach ($videos as $video_url) {
                    AulaVideo::create([
                        'aula_id' => $aula->id,
                        'url' => $video_url
                    ]);
                }
            }

            DB::commit();

            // Cargar relaciones para la respuesta
            $aula->load('fotos', 'videos');
            FacadesCache::clear();

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

    public function destroy($id): JsonResponse {
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
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
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

            // Eliminar fotos del storage antes de eliminar el aula
            foreach ($aula->fotos as $foto) {
                Storage::disk('public')->delete($foto->ruta);
            }

            $aula->delete();

            DB::commit();

            return response()->json([
                'message' => 'Aula eliminada exitosamente',
                'success' => true
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();

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


            $aula_model = aulas::where('qr_code', $qr_code)->first();

            if (!$aula_model) {
                return response()->json([
                    'message' => 'Aula no encontrada con el QR code especificado',
                    'success' => false
                ], 404);
            }


            $aulas_con_recursos = (new aulas())->getAllById($aula_model->id);


            $aula_data = null;
            foreach ($aulas_con_recursos as $aula_recurso) {
                if (!$aula_data) {
                    $aula_data = [
                        'id' => $aula_recurso->aula_id,
                        'codigo' => $aula_recurso->codigo_aula,
                        'nombre' => $aula_recurso->nombre_aula,
                        'capacidad_pupitres' => $aula_recurso->capacidad_pupitres,
                        'ubicacion' => $aula_recurso->ubicacion_aula,
                        'qr_code' => $aula_recurso->qr_code,
                        'estado' => $aula_recurso->estado_aula,
                        'created_at' => $aula_recurso->created_at,
                        'updated_at' => $aula_recurso->updated_at,
                        'recursos' => []
                    ];
                }
                if ($aula_recurso->recurso_tipo_nombre) {
                    $aula_data['recursos'][] = [
                        'nombre' => $aula_recurso->recurso_tipo_nombre,
                        'cantidad' => $aula_recurso->recurso_cantidad,
                        'estado' => $aula_recurso->estado_recurso,
                        'observaciones_recurso' => $aula_recurso->observaciones_recurso,
                        'aula_recurso_id' => $aula_recurso->aula_id
                    ];
                }
            }


            if (!$aula_data) {
                $aula_data = $aula_model->toArray();
                $aula_data['recursos'] = [];
            }

            $storage_url = env('APP_URL') . '/storage';

            // Agregar indicaciones, coordenadas, fotos y videos
            $aula_data['indicaciones'] = $aula_model->indicaciones;
            $aula_data['latitud'] = $aula_model->latitud;
            $aula_data['longitud'] = $aula_model->longitud;

            $aula_data['fotos'] = $aula_model->fotos->map(function ($foto) use ($storage_url) {
                return [
                    'id' => $foto->id,
                    'url' => $storage_url . '/' . $foto->ruta
                ];
            })->toArray();

            $aula_data['videos'] = $aula_model->videos->map(function ($video) {
                return [
                    'id' => $video->id,
                    'url' => $video->url
                ];
            })->toArray();

            return response()->json([
                'message' => 'Aula obtenida exitosamente',
                'success' => true,
                'data' => $aula_data
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

            $user_rolName = $this->getUserRoleName();
            if ($user_rolName != RolesEnum::ADMINISTRADOR_ACADEMICO->value) {
                return response()->json([
                    'message' => 'Acceso no autorizado',
                    'success' => false
                ], 403);
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

    private function getUserRoleId() {
        return DB::table('usuario_roles')
            ->join('users', 'usuario_roles.usuario_id', '=', 'users.id')
            ->where('users.id', Auth::id())
            ->value('usuario_roles.rol_id');
    }

    public function getClassroomStatistics(Request $request, $id): JsonResponse {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'message' => 'Acceso no autorizado',
                    'success' => false
                ], 401);
            }

            $user_rol = $this->getUserRoleId();
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

    public function getPaginatedClassrooms(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        // Sanitizar parámetros
        $request->merge([
            'page' => (int)$request->get('page', 1),
            'per_page' => (int)$request->get('per_page', 9),
            'search' => $this->sanitizeInput($request->get('search', '')),
            'estado' => $this->sanitizeInput($request->get('estado', '')),
            'capacidad' => $this->sanitizeInput($request->get('capacidad', '')),
            'sort_by' => $this->sanitizeInput($request->get('sort_by', 'nombre')),
            'sort_dir' => $this->sanitizeInput($request->get('sort_dir', 'asc'))
        ]);

        $rules = [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|in:10,15,25,50,100',
            'search' => 'nullable|string|max:255',
            'estado' => 'nullable|in:disponible,ocupada,mantenimiento,inactiva',
            'capacidad' => 'nullable|in:small,medium,large',
            'sort_by' => 'nullable|in:nombre,codigo,capacidad_pupitres,ubicacion,estado',
            'sort_dir' => 'nullable|in:asc,desc'
        ];

        $messages = [
            'page.integer' => 'El número de página debe ser un entero.',
            'page.min' => 'El número de página debe ser al menos 1.',
            'per_page.integer' => 'Los registros por página debe ser un entero.',
            'per_page.in' => 'Los registros por página debe ser uno de: 10, 15, 25, 50, 100.',
            'search.max' => 'La búsqueda no debe exceder 255 caracteres.',
            'estado.in' => 'El estado debe ser uno de: disponible, ocupada, mantenimiento, inactiva.',
            'capacidad.in' => 'La capacidad debe ser uno de: small, medium, large.',
            'sort_by.in' => 'El ordenamiento debe ser por: nombre, codigo, capacidad_pupitres, ubicacion, estado.',
            'sort_dir.in' => 'La dirección debe ser: asc o desc.'
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
            $params = $validator->validated();

            // Cache key dinámica
            $cacheKey = 'aulas_paginated_' . md5(json_encode([
                'search' => $params['search'],
                'estado' => $params['estado'],
                'capacidad' => $params['capacidad'],
                'sort_by' => $params['sort_by'],
                'sort_dir' => $params['sort_dir'],
                'page' => $params['page'],
                'per_page' => $params['per_page']
            ]));

            return cache()->remember($cacheKey, 300, function () use ($params) {
                // Query base replicando la lógica del método getAll() del modelo
                $query = DB::table('aulas')
                    ->leftJoin('aula_recursos', 'aulas.id', '=', 'aula_recursos.aula_id')
                    ->leftJoin('recursos_tipos', 'aula_recursos.recurso_tipo_id', '=', 'recursos_tipos.id')
                    ->select(
                        'aulas.id as aula_id',
                        'aulas.codigo as codigo_aula',
                        'aulas.nombre as nombre_aula',
                        'aulas.capacidad_pupitres',
                        'aulas.ubicacion as ubicacion_aula',
                        'aulas.qr_code as qr_code',
                        'aulas.estado as estado_aula',
                        'aulas.created_at',
                        'aulas.updated_at',
                        'recursos_tipos.nombre as recurso_tipo_nombre',
                        'aula_recursos.cantidad as recurso_cantidad',
                        'aula_recursos.estado as estado_recurso',
                        'aula_recursos.observaciones as observaciones_recurso'
                    );

                // Aplicar filtros
                if (!empty($params['search'])) {
                    $searchTerm = '%' . $params['search'] . '%';
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where('aulas.nombre', 'LIKE', $searchTerm)
                          ->orWhere('aulas.codigo', 'LIKE', $searchTerm)
                          ->orWhere('aulas.ubicacion', 'LIKE', $searchTerm);
                    });
                }

                if (!empty($params['estado'])) {
                    $query->where('aulas.estado', $params['estado']);
                }

                if (!empty($params['capacidad'])) {
                    switch ($params['capacidad']) {
                        case 'small':
                            $query->where('aulas.capacidad_pupitres', '<=', 30);
                            break;
                        case 'medium':
                            $query->where('aulas.capacidad_pupitres', '>', 30)
                                  ->where('aulas.capacidad_pupitres', '<=', 100);
                            break;
                        case 'large':
                            $query->where('aulas.capacidad_pupitres', '>', 100);
                            break;
                    }
                }

                // Contar total antes de paginar
                $countQuery = clone $query;
                $total = $countQuery->distinct('aulas.id')->count('aulas.id');

                // Ordenamiento
                $sortMapping = [
                    'nombre' => 'aulas.nombre',
                    'codigo' => 'aulas.codigo',
                    'capacidad_pupitres' => 'aulas.capacidad_pupitres',
                    'ubicacion' => 'aulas.ubicacion',
                    'estado' => 'aulas.estado'
                ];

                $sortField = $sortMapping[$params['sort_by']] ?? 'aulas.nombre';
                $query->orderBy($sortField, $params['sort_dir']);

                // Calcular offset y limit
                $offset = ($params['page'] - 1) * $params['per_page'];
                $query->offset($offset)->limit($params['per_page']);

                // Ejecutar query
                $aulas_raw = $query->get();

                // Procesar resultados (similar al método index())
                $aulas_array = [];
                foreach ($aulas_raw as $aula) {
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
                            'created_at' => $aula->created_at,
                            'updated_at' => $aula->updated_at,
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

                // Agregar fotos y videos con URLs completas
                $storage_url = env('APP_URL') . '/storage';
                foreach ($aulas as &$aula) {
                    $aula_model = aulas::find($aula['id']);

                    // Agregar indicaciones y coordenadas
                    $aula['indicaciones'] = $aula_model->indicaciones;
                    $aula['latitud'] = $aula_model->latitud;
                    $aula['longitud'] = $aula_model->longitud;

                    // Agregar fotos
                    $aula['fotos'] = $aula_model->fotos->map(function ($foto) use ($storage_url) {
                        return [
                            'id' => $foto->id,
                            'url' => $storage_url . '/' . $foto->ruta
                        ];
                    })->toArray();

                    // Agregar videos
                    $aula['videos'] = $aula_model->videos->map(function ($video) {
                        return [
                            'id' => $video->id,
                            'url' => $video->url
                        ];
                    })->toArray();
                }

                // Calcular metadata de paginación
                $ultimaPagina = ceil($total / $params['per_page']);

                return response()->json([
                    'message' => 'Aulas obtenidas exitosamente',
                    'success' => true,
                    'data' => $aulas,
                    'pagination' => [
                        'pagina_actual' => $params['page'],
                        'por_pagina' => $params['per_page'],
                        'total' => $total,
                        'ultima_pagina' => $ultimaPagina,
                        'desde' => $total > 0 ? $offset + 1 : null,
                        'hasta' => $total > 0 ? min($offset + $params['per_page'], $total) : null
                    ]
                ], 200);
            });

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las aulas paginadas',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    
}
