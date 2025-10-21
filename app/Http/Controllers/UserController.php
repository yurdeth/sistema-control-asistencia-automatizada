<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\RolesEnum;
use App\Rules\FormatEmailRule;
use App\Rules\PasswordFormatRule;
use App\Rules\PhoneRule;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller {
    /**
     * Display a listing of the resource.
     */
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
            RolesEnum::DOCENTE->value,
        ];

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos)) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        $user_rol = $this->getUserRoleId();

        $users = Cache::remember('all_users', 60, function () use ($user_rol) {
            return (new User())->getUsersBasedOnMyUserRole($user_rol);
        });

        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No se encontraron usuarios',
                'success' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Usuarios encontrados',
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse {
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

        $request->merge([
            'nombre_completo' => $this->sanitizeInput($request->input('nombre_completo')),
            'email' => $this->sanitizeInput($request->input('email')),
            'telefono' => $this->sanitizeInput($request->input('telefono')),
            'password' => $this->sanitizeInput($request->input('password')),
            'password_confirmation' => $this->sanitizeInput($request->input('password_confirmation')),
            'departamento_id' => $request->input('departamento_id') ? $this->sanitizeInput($request->input('departamento_id')) : null,
            'carrera_id' => $request->input('carrera_id') ? $this->sanitizeInput($request->input('carrera_id')) : null,
            'rol_id' => $this->sanitizeInput($request->input('rol_id')),
            'estado' => $this->sanitizeInput($request->input('estado')),
        ]);

        $rules = [
            'nombre_completo' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZñÑáÁéÉíÍóÓúÚ\s]+$/'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', new FormatEmailRule()],
            'telefono' => ['nullable', 'string', 'max:20', new PhoneRule()],
            'password' => ['required', 'string', 'min:8', new PasswordFormatRule()],
            'password_confirmation' => 'required|string|same:password',
            'rol_id' => 'required|integer|exists:roles,id',
            'estado' => 'required|in:activo,inactivo,suspendido',
        ];

        $messages = [
            'nombre_completo.required' => 'El nombre completo es obligatorio.',
            'nombre_completo.string' => 'El nombre completo debe ser una cadena de texto.',
            'nombre_completo.max' => 'El nombre completo no debe exceder los 255 caracteres.',
            'nombre_completo.regex' => 'El nombre completo no debe contener números ni caracteres especiales.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.string' => 'El correo electrónico debe ser una cadena de texto.',
            'email.email' => 'El correo electrónico debe ser una dirección de correo válida.',
            'email.max' => 'El correo electrónico no debe exceder los 255 caracteres.',
            'email.unique' => 'El correo electrónico ya está en uso.',
            'telefono.string' => 'El teléfono debe ser una cadena de texto.',
            'telefono.max' => 'El teléfono no debe exceder los 20 caracteres.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser una cadena de texto.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password_confirmation.required' => 'La confirmación de la contraseña es obligatoria.',
            'password_confirmation.string' => 'La confirmación de la contraseña debe ser una cadena de texto.',
            'password_confirmation.same' => 'La confirmación de la contraseña no coincide con la contraseña.',
            'departamento_id.required' => 'El ID del departamento es obligatorio para este rol.',
            'departamento_id.integer' => 'El ID del departamento debe ser un número entero.',
            'departamento_id.exists' => 'El ID del departamento no existe.',
            'departamento_id.prohibited' => 'El departamento no debe ser especificado para este rol.',
            'carrera_id.required' => 'El ID de la carrera es obligatorio para este rol.',
            'carrera_id.integer' => 'El ID de la carrera debe ser un número entero.',
            'carrera_id.exists' => 'El ID de la carrera no existe.',
            'carrera_id.prohibited' => 'La carrera no debe ser especificada para este rol.',
            'rol_id.required' => 'El ID del rol es obligatorio.',
            'rol_id.integer' => 'El ID del rol debe ser un número entero.',
            'rol_id.exists' => 'El ID del rol no existe.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser uno de los siguientes: activo, inactivo, suspendido.',
        ];

        // Validación condicional basada en el rol_id
        $rol_id = $request->input('rol_id');
        $departamento_id = $request->input('departamento_id');
        $carrera_id = $request->input('carrera_id');

        // Validaciones según el rol
        switch ((int)$rol_id) {
            case 1:
            case 2:
            case 7:
                //Ni departamento_id ni carrera_id
                $rules['departamento_id'] = 'prohibited';
                $rules['carrera_id'] = 'prohibited';
                break;

            case 3:
                //departamento_id (obligatorio)
                $rules['departamento_id'] = 'required|integer|exists:departamentos,id';
                $rules['carrera_id'] = 'prohibited';
                break;

            case 4:
            case 6:
                $rules['carrera_id'] = 'required|integer|exists:carreras,id';
                $rules['departamento_id'] = 'prohibited';
                break;

            case 5:
                // Puede tener departamento_id O carrera_id (uno de los dos obligatorio, pero no ambos)
                if (!empty($departamento_id) && !empty($carrera_id)) {
                    return response()->json([
                        'message' => 'Error de validación',
                        'errors' => [
                            'departamento_id' => ['No se puede especificar departamento y carrera al mismo tiempo para un docente.'],
                            'carrera_id' => ['No se puede especificar departamento y carrera al mismo tiempo para un docente.']
                        ],
                        'success' => false
                    ], 422);
                }

                if (empty($departamento_id) && empty($carrera_id)) {
                    return response()->json([
                        'message' => 'Error de validación',
                        'errors' => [
                            'departamento_id' => ['Se debe especificar un departamento o una carrera para un docente.'],
                            'carrera_id' => ['Se debe especificar un departamento o una carrera para un docente.']
                        ],
                        'success' => false
                    ], 422);
                }

                if (!empty($departamento_id)) {
                    $rules['departamento_id'] = 'required|integer|exists:departamentos,id';
                    $rules['carrera_id'] = 'nullable';
                } else {
                    $rules['carrera_id'] = 'required|integer|exists:carreras,id';
                    $rules['departamento_id'] = 'nullable';
                }
                break;

            default:
                // Para roles no definidos, permitir null en ambos
                $rules['departamento_id'] = 'nullable|integer|exists:departamentos,id';
                $rules['carrera_id'] = 'nullable|integer|exists:carreras,id';
                break;
        }

        try {
            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                    'success' => false
                ], 422);
            }

            DB::beginTransaction();

            $validatedData = $validator->validated();

            $telefono = str_starts_with($validatedData['telefono'] ?? '', "+503")
                ? $validatedData['telefono']
                : "+503 " . ($validatedData['telefono'] ?? '');
            $telefono = preg_replace('/(\+503)\s?(\d{4})(\d{4})/', '$1 $2-$3', $telefono);

            $user = new User();
            $user->nombre_completo = $validatedData['nombre_completo'];
            $user->email = $validatedData['email'];
            $user->telefono = $telefono;
            $user->password = Hash::make($validatedData['password']);
            $user->departamento_id = $validatedData['departamento_id'] ?? null;
            $user->carrera_id = $validatedData['carrera_id'] ?? null;
            $user->estado = $validatedData['estado'];
            $user->save();

            DB::table('usuario_roles')->insert([
                'usuario_id' => $user->id,
                'rol_id' => $validatedData['rol_id'],
                'asignado_por_id' => Auth::id(),
            ]);

            DB::commit();

            // Borrar el caché relacionado con los usuarios
            Cache::forget('all_users');
            Cache::forget('users_by_name');
            Cache::forget('users_by_role');
            Cache::forget('users_by_department');
            Cache::forget('users_by_status');
            Cache::forget('users_by_subject');
            Cache::forget('administradores_academicos');
            Cache::forget('coordinadores');
            Cache::forget('professors_only');

            return response()->json([
                'message' => 'Usuario creado exitosamente',
                'success' => true,
                'data' => $user
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear el usuario',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse {
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

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos) && Auth::user()->id != $id) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        $user_rol = $this->getUserRoleId();
        $user = (new User())->getUserBasedOnMyUserRole($user_rol, $id);

        Log::info($user);

        if (!$user || $user->isEmpty()) {
            return response()->json([
                'message' => 'Usuario no encontrado',
                'success' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Usuario encontrado',
            'success' => true,
            'data' => $user
        ]);
    }

    public function edit(Request $request, int $id): JsonResponse {
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

        if (!in_array($user_rolName?->value ?? $user_rolName, $rolesPermitidos) && Auth::user()->id != $id) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 403);
        }

        $dataToMerge = [];

        if ($request->has('nombre_completo')) {
            $dataToMerge['nombre_completo'] = $this->sanitizeInput($request->input('nombre_completo', ''));
        }

        if ($request->has('email')) {
            $dataToMerge['email'] = $this->sanitizeInput($request->input('email', ''));
        }

        if ($request->has('telefono')) {
            $dataToMerge['telefono'] = $this->sanitizeInput($request->input('telefono', ''));
        }

        if ($request->has('password')) {
            $dataToMerge['password'] = $this->sanitizeInput($request->input('password', ''));
        }

        if ($request->has('password_confirmation')) {
            $dataToMerge['password_confirmation'] = $this->sanitizeInput($request->input('password_confirmation', ''));
        }

        if ($request->has('departamento_id')) {
            $dataToMerge['departamento_id'] = $request->input('departamento_id') ? $this->sanitizeInput($request->input('departamento_id')) : null;
        }

        if ($request->has('carrera_id')) {
            $dataToMerge['carrera_id'] = $request->input('carrera_id') ? $this->sanitizeInput($request->input('carrera_id')) : null;
        }

        if ($request->has('rol_id')) {
            $dataToMerge['rol_id'] = $this->sanitizeInput($request->input('rol_id', ''));
        }

        if ($request->has('estado')) {
            $dataToMerge['estado'] = $this->sanitizeInput($request->input('estado', ''));
        }

        $request->merge($dataToMerge);

        $rules = [
            'nombre_completo' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZñÑáÁéÉíÍóÓúÚ\s]+$/'],
            'email' => [
                'sometimes', 'required', 'string', 'email', 'max:255',
                'unique:users,email,' . $id, new FormatEmailRule()
            ],
            'telefono' => ['nullable', 'string', 'max:20', new PhoneRule()],
            'rol_id' => 'sometimes|required|integer|exists:roles,id',
            'estado' => 'sometimes|required|in:activo,inactivo,suspendido',
        ];

        if ($request->has('password')) {
            $rules['password'] = ['required', 'string', 'min:8', new PasswordFormatRule()];
            $rules['password_confirmation'] = 'required|string|same:password';
        }

        $messages = [
            'nombre_completo.required' => 'El nombre completo es obligatorio.',
            'nombre_completo.string' => 'El nombre completo debe ser una cadena de texto.',
            'nombre_completo.max' => 'El nombre completo no debe exceder los 255 caracteres.',
            'nombre_completo.regex' => 'El nombre completo no debe contener números ni caracteres especiales.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.string' => 'El correo electrónico debe ser una cadena de texto.',
            'email.email' => 'El correo electrónico debe ser una dirección de correo válida.',
            'email.max' => 'El correo electrónico no debe exceder los 255 caracteres.',
            'email.unique' => 'El correo electrónico ya está en uso.',
            'telefono.string' => 'El teléfono debe ser una cadena de texto.',
            'telefono.max' => 'El teléfono no debe exceder los 20 caracteres.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser una cadena de texto.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password_confirmation.required' => 'La confirmación de la contraseña es obligatoria.',
            'password_confirmation.string' => 'La confirmación de la contraseña debe ser una cadena de texto.',
            'password_confirmation.same' => 'La confirmación de la contraseña no coincide con la contraseña.',
            'departamento_id.required' => 'El ID del departamento es obligatorio para este rol.',
            'departamento_id.integer' => 'El ID del departamento debe ser un número entero.',
            'departamento_id.exists' => 'El ID del departamento no existe.',
            'departamento_id.prohibited' => 'El departamento no debe ser especificado para este rol.',
            'carrera_id.required' => 'El ID de la carrera es obligatorio para este rol.',
            'carrera_id.integer' => 'El ID de la carrera debe ser un número entero.',
            'carrera_id.exists' => 'El ID de la carrera no existe.',
            'carrera_id.prohibited' => 'La carrera no debe ser especificada para este rol.',
            'rol_id.required' => 'El ID del rol es obligatorio.',
            'rol_id.integer' => 'El ID del rol debe ser un número entero.',
            'rol_id.exists' => 'El ID del rol no existe.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser uno de los siguientes: activo, inactivo, suspendido.',
        ];

        // Validación condicional basada en el rol_id (si se está actualizando)
        $rol_id = $request->input('rol_id');

        // Si se está actualizando el rol, aplicar validaciones condicionales
        if ($request->has('rol_id')) {
            // Obtener el usuario actual para verificar sus valores existentes
            $currentUser = User::find($id);

            $departamento_id = $request->input('departamento_id');
            $carrera_id = $request->input('carrera_id');

            // Validaciones según el rol
            switch ((int)$rol_id) {
                case 1: // root
                case 2: // administrador_academico
                case 7: // invitado
                    // NO deben tener ni departamento_id ni carrera_id
                    if ($request->has('departamento_id')) {
                        $rules['departamento_id'] = 'prohibited';
                    }
                    if ($request->has('carrera_id')) {
                        $rules['carrera_id'] = 'prohibited';
                    }
                    break;

                case 3: // jefe_departamento
                    // SOLO debe tener departamento_id (obligatorio)
                    // Si no se proporciona en la petición, verificar que el usuario ya tenga uno
                    if (!$request->has('departamento_id') && !$currentUser->departamento_id) {
                        return response()->json([
                            'message' => 'Error de validación',
                            'errors' => [
                                'departamento_id' => ['El departamento es obligatorio para el rol de jefe de departamento.']
                            ],
                            'success' => false
                        ], 422);
                    }

                    if ($request->has('departamento_id')) {
                        $rules['departamento_id'] = 'required|integer|exists:departamentos,id';
                    }
                    if ($request->has('carrera_id')) {
                        $rules['carrera_id'] = 'prohibited';
                    }
                    break;

                case 4: // coordinador_carreras
                case 6: // estudiante
                    // SOLO debe tener carrera_id (obligatorio)
                    // Si no se proporciona en la petición, verificar que el usuario ya tenga una
                    if (!$request->has('carrera_id') && !$currentUser->carrera_id) {
                        return response()->json([
                            'message' => 'Error de validación',
                            'errors' => [
                                'carrera_id' => ['La carrera es obligatoria para este rol.']
                            ],
                            'success' => false
                        ], 422);
                    }

                    if ($request->has('carrera_id')) {
                        $rules['carrera_id'] = 'required|integer|exists:carreras,id';
                    }
                    if ($request->has('departamento_id')) {
                        $rules['departamento_id'] = 'prohibited';
                    }
                    break;

                case 5: // docente
                    // Puede tener departamento_id O carrera_id (uno de los dos obligatorio, pero no ambos)
                    if (!empty($departamento_id) && !empty($carrera_id)) {
                        return response()->json([
                            'message' => 'Error de validación',
                            'errors' => [
                                'departamento_id' => ['No se puede especificar departamento y carrera al mismo tiempo para un docente.'],
                                'carrera_id' => ['No se puede especificar departamento y carrera al mismo tiempo para un docente.']
                            ],
                            'success' => false
                        ], 422);
                    }

                    // Verificar que tenga al menos uno (en la petición o en la BD)
                    $tieneDepartamento = $request->has('departamento_id') ? !empty($departamento_id) : !empty($currentUser->departamento_id);
                    $tieneCarrera = $request->has('carrera_id') ? !empty($carrera_id) : !empty($currentUser->carrera_id);

                    if (!$tieneDepartamento && !$tieneCarrera) {
                        return response()->json([
                            'message' => 'Error de validación',
                            'errors' => [
                                'departamento_id' => ['Se debe especificar un departamento o una carrera para un docente.'],
                                'carrera_id' => ['Se debe especificar un departamento o una carrera para un docente.']
                            ],
                            'success' => false
                        ], 422);
                    }

                    if ($request->has('departamento_id')) {
                        $rules['departamento_id'] = !empty($departamento_id) ? 'required|integer|exists:departamentos,id' : 'nullable';
                    }
                    if ($request->has('carrera_id')) {
                        $rules['carrera_id'] = !empty($carrera_id) ? 'required|integer|exists:carreras,id' : 'nullable';
                    }
                    break;

                default:
                    // Para roles no definidos, permitir null en ambos
                    $rules['departamento_id'] = 'sometimes|nullable|integer|exists:departamentos,id';
                    $rules['carrera_id'] = 'sometimes|nullable|integer|exists:carreras,id';
                    break;
            }
        } else {
            // Si no se está actualizando el rol, solo validar si se proporcionan los campos
            $rules['departamento_id'] = 'sometimes|nullable|integer|exists:departamentos,id';
            $rules['carrera_id'] = 'sometimes|nullable|integer|exists:carreras,id';
        }

        try {
            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                    'success' => false
                ], 422);
            }

            DB::beginTransaction();

            $user = User::where('id', $id)->lockForUpdate()->firstOrFail();

            $validatedData = $validator->validated();

            if (isset($validatedData['nombre_completo'])) {
                $user->nombre_completo = $validatedData['nombre_completo'];
            }

            if (isset($validatedData['email'])) {
                $user->email = $validatedData['email'];
            }

            if (isset($validatedData['telefono'])) {
                $telefono = str_starts_with($validatedData['telefono'], "+503")
                    ? $validatedData['telefono']
                    : "+503 " . $validatedData['telefono'];
                $telefono = preg_replace('/(\+503)\s?(\d{4})(\d{4})/', '$1 $2-$3', $telefono);
                $user->telefono = $telefono;
            }

            if (isset($validatedData['password'])) {
                $user->password = Hash::make($validatedData['password']);
            }

            if (isset($validatedData['departamento_id'])) {
                $user->departamento_id = $validatedData['departamento_id'];
            }

            if (isset($validatedData['carrera_id'])) {
                $user->carrera_id = $validatedData['carrera_id'];
            }

            if (isset($validatedData['estado'])) {
                $user->estado = $validatedData['estado'];
            }

            // Si se está cambiando el rol, limpiar departamento_id y carrera_id según las reglas del nuevo rol
            if (isset($validatedData['rol_id'])) {
                $new_rol_id = (int)$validatedData['rol_id'];

                switch ($new_rol_id) {
                    case 1: // root
                    case 2: // administrador_academico
                    case 7: // invitado
                        // Estos roles NO deben tener ni departamento_id ni carrera_id
                        $user->departamento_id = null;
                        $user->carrera_id = null;
                        break;

                    case 3: // jefe_departamento
                        // SOLO debe tener departamento_id, limpiar carrera_id
                        $user->carrera_id = null;
                        // Si no se proporcionó departamento_id en la actualización, mantener el actual
                        break;

                    case 4: // coordinador_carreras
                    case 6: // estudiante
                        // SOLO debe tener carrera_id, limpiar departamento_id
                        $user->departamento_id = null;
                        // Si no se proporcionó carrera_id en la actualización, mantener el actual
                        break;

                    case 5: // docente
                        // Puede tener departamento_id O carrera_id
                        // Si se proporcionó uno, limpiar el otro
                        if (isset($validatedData['departamento_id']) && $validatedData['departamento_id']) {
                            $user->carrera_id = null;
                        } elseif (isset($validatedData['carrera_id']) && $validatedData['carrera_id']) {
                            $user->departamento_id = null;
                        }
                        // Si no se proporcionó ninguno, mantener el que ya tiene
                        break;
                }
            }

            $user->save();

            if (isset($validatedData['rol_id'])) {
                DB::table('usuario_roles')
                    ->where('usuario_id', $user->id)
                    ->update([
                        'rol_id' => $validatedData['rol_id'],
                        'asignado_por_id' => Auth::id(),
                    ]);
            }

            DB::commit();

            // Borrar el caché relacionado con los usuarios
            Cache::forget('all_users');
            Cache::forget('users_by_name');
            Cache::forget('users_by_role');
            Cache::forget('users_by_department');
            Cache::forget('users_by_status');
            Cache::forget('users_by_subject');
            Cache::forget('administradores_academicos');
            Cache::forget('coordinadores');
            Cache::forget('professors_only');

            return response()->json([
                'message' => 'Usuario actualizado exitosamente',
                'success' => true,
                'data' => $user
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar el usuario',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user_rolName = $this->getUserRoleName();
        if ($user_rolName != RolesEnum::ROOT->value) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            DB::beginTransaction();
            $user = User::where('id', $id)->lockForUpdate()->first();

            if (!$user) {
                return response()->json([
                    'message' => 'No se encontró el usuario',
                    'success' => false
                ], 404);
            }

            if ($user->id == 1) {
                return response()->json([
                    'message' => 'No se puede eliminar el usuario administrador principal',
                    'success' => false
                ], 403);
            }

            $user->delete();
            DB::commit();

            // Borrar el caché relacionado con los usuarios
            Cache::forget('all_users');
            Cache::forget('users_by_name');
            Cache::forget('users_by_role');
            Cache::forget('users_by_department');
            Cache::forget('users_by_status');
            Cache::forget('users_by_subject');
            Cache::forget('administradores_academicos');
            Cache::forget('coordinadores');
            Cache::forget('professors_only');

            return response()->json([
                'message' => 'Usuario eliminado exitosamente',
                'success' => true
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar el usuario',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function disableAccount(Request $request): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user_rolName = $this->getUserRoleName();
        if ($user_rolName != RolesEnum::ROOT->value) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            DB::beginTransaction();
            $user = User::where('id', $request->id)->lockForUpdate()->first();

            if (!$user) {
                return response()->json([
                    'message' => 'No se encontró el usuario',
                    'success' => false
                ], 404);
            }

            if ($user->id == 1) {
                return response()->json([
                    'message' => 'No se puede desactivar el usuario administrador principal',
                    'success' => false
                ], 403);
            }

            if($user->estado == 'inactivo') {
                return response()->json([
                    'message' => 'El usuario ya está inactivo',
                    'success' => false
                ], 400);
            }

            $user->estado = 'inactivo';
            $user->save();
            DB::commit();

            return response()->json([
                'message' => 'Usuario desactivado exitosamente',
                'success' => true
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al desactivar el usuario',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function enableAccount(Request $request): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user_rolName = $this->getUserRoleName();
        if ($user_rolName != RolesEnum::ROOT->value) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            DB::beginTransaction();
            $user = User::where('id', $request->id)->lockForUpdate()->first();

            if ($user->id == 1) {
                return response()->json([
                    'message' => 'No se puede desactivar el usuario administrador principal',
                    'success' => false
                ], 403);
            }

            if (!$user) {
                return response()->json([
                    'message' => 'No se encontró el usuario',
                    'success' => false
                ], 404);
            }

            if($user->estado == 'activo') {
                return response()->json([
                    'message' => 'El usuario ya está activo',
                    'success' => false
                ], 400);
            }

            $user->estado = 'activo';
            $user->save();
            DB::commit();

            return response()->json([
                'message' => 'Usuario activado exitosamente',
                'success' => true
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al desactivar el usuario',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function getByName(Request $request): JsonResponse {
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
            ], 401);
        }

        $name = $this->sanitizeInput($request->nombre);
        $users = Cache::remember('users_by_name', 60, function () use ($name) {
            return (new User())->getAllUsers()->filter(function ($user) use ($name) {
                return stripos($user->nombre_completo, $name) !== false;
            });
        });

        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No se encontraron usuarios con el nombre especificado',
                'success' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Usuarios encontrados',
            'success' => true,
            'data' => $users
        ]);
    }

    public function getByRole(int $role_id): JsonResponse {
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
            ], 401);
        }

        $users = Cache::remember('users_by_role', 60, function () use ($role_id) {
            return (new User())->getUsersByRole($role_id);
        });

        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No se encontraron usuarios con el rol especificado',
                'success' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Usuarios encontrados',
            'success' => true,
            'data' => $users
        ]);
    }

    public function getByDepartment(int $department_id): JsonResponse {
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
            ], 401);
        }

        $users = Cache::remember('users_by_department', 60, function () use ($department_id) {
            return (new User())->getUsersByDepartment($department_id);
        });

        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No se encontraron usuarios en el departamento especificado',
                'success' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Usuarios encontrados',
            'success' => true,
            'data' => $users
        ]);
    }

    public function getByStatus(Request $request): JsonResponse {
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
            ], 401);
        }

        $status = $this->sanitizeInput($request->estado);
        if (!in_array($status, ['activo', 'inactivo', 'suspendido'])) {
            return response()->json([
                'message' => 'Estado inválido',
                'success' => false
            ], 422);
        }

        $users = Cache::remember('users_by_status', 60, function () use ($status) {
            return (new User())->getUsersByStatus($status);
        });

        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No se encontraron usuarios con el estado especificado',
                'success' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Usuarios encontrados',
            'success' => true,
            'data' => $users
        ]);
    }

    public function getBySubject(int $subject_id): JsonResponse {
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
            ], 401);
        }

        $users = Cache::remember('users_by_subject', 60, function () use ($subject_id) {
            return (new User())->getUsersBySubject($subject_id);
        });

        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No se encontraron usuarios asociados a la materia especificada',
                'success' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Usuarios encontrados',
            'success' => true,
            'data' => $users
        ]);
    }

    public function getAdministradoresAcademicosOnly(): JsonResponse {
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
            ], 401);
        }

        $users = Cache::remember('administradores_academicos', 60, function () {
            return (new User())->getAdministradoresAcademicosOnly();
        });

        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No se encontraron administradores académicos',
                'success' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Administradores académicos encontrados',
            'success' => true,
            'data' => $users
        ]);
    }

    public function getDepartmentManagersOnly(): JsonResponse {
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
            ], 401);
        }

        $users = Cache::remember('coordinadores', 60, function () {
            return (new User())->getDepartmentManagersOnly();
        });

        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No se encontraron coordinadores',
                'success' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Coordinadores encontrados',
            'success' => true,
            'data' => $users
        ]);
    }

    public function getCareerManagersOnly(): JsonResponse {
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
            ], 401);
        }

        $users = Cache::remember('career_managers_only', 60, function () {
            return (new User())->getCareerManagersOnly();
        });

        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No se encontraron coordinadores de carrera',
                'success' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Coordinadores de carrera encontrados',
            'success' => true,
            'data' => $users
        ]);
    }

    public function getProfessorsOnly(): JsonResponse {
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
            ], 401);
        }

        $users = Cache::remember('professors_only', 60, function () {
            return (new User())->getProfessorsOnly();
        });

        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No se encontraron docentes',
                'success' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Docentes encontrados',
            'success' => true,
            'data' => $users
        ]);
    }

    public function getStudentsOnly(): JsonResponse {
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
            ], 401);
        }

        $users = Cache::remember('students_only', 60, function () {
            return (new User())->getStudentsOnly();
        });

        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No se encontraron estudiantes',
                'success' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Estudiantes encontrados',
            'success' => true,
            'data' => $users
        ]);
    }

    public function getMyProfile(): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user = (new User())->myProfile(Auth::user()->id)->first();
        $departamento_nombre = DB::table('departamentos')
            ->join('users', 'departamentos.id', '=', 'users.departamento_id')
            ->where('users.id', $user->id)
            ->value('departamentos.nombre');

        $user->departamento_nombre = $departamento_nombre;

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado',
                'success' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Perfil del usuario encontrado',
            'success' => true,
            'data' => $user
        ]);
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
