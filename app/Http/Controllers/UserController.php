<?php

namespace App\Http\Controllers;

use App\Models\User;
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

        $user_rol = $this->getUserRole();
        if ($user_rol == 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

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

        $user_rol = $this->getUserRole();
        if ($user_rol == 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $request->merge([
            'nombre_completo' => $this->sanitizeInput($request->input('nombre_completo')),
            'email' => $this->sanitizeInput($request->input('email')),
            'telefono' => $this->sanitizeInput($request->input('telefono')),
            'password' => $this->sanitizeInput($request->input('password')),
            'password_confirmation' => $this->sanitizeInput($request->input('password_confirmation')),
            'departamento_id' => $this->sanitizeInput($request->input('departamento_id')),
            'rol_id' => $this->sanitizeInput($request->input('rol_id')),
            'estado' => $this->sanitizeInput($request->input('estado')),
        ]);

        $rules = [
            'nombre_completo' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZñÑáÁéÉíÍóÓúÚ\s]+$/'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', new FormatEmailRule()],
            'telefono' => ['nullable', 'string', 'max:20', new PhoneRule()],
            'password' => ['required', 'string', 'min:8', new PasswordFormatRule()],
            'password_confirmation' => 'required|string|same:password',
            'departamento_id' => 'required|integer|exists:departamentos,id',
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
            'departamento_id.required' => 'El ID del departamento es obligatorio.',
            'departamento_id.integer' => 'El ID del departamento debe ser un número entero.',
            'departamento_id.exists' => 'El ID del departamento no existe.',
            'rol_id.required' => 'El ID del rol es obligatorio.',
            'rol_id.integer' => 'El ID del rol debe ser un número entero.',
            'rol_id.exists' => 'El ID del rol no existe.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser uno de los siguientes: activo, inactivo, suspendido.',
        ];

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
            $user->departamento_id = $validatedData['departamento_id'];
            $user->estado = $validatedData['estado'];
            $user->save();

            DB::table('usuario_roles')->insert([
                'usuario_id' => $user->id,
                'rol_id' => $validatedData['rol_id'],
                'asignado_por_id' => Auth::id(),
            ]);

            DB::commit();

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

        $user_rol = $this->getUserRole();

        if ($user_rol != 1 || Auth::user()->id != $id) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user = (new User())->getUser($id)->first();

        if (!$user) {
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

        if (Auth::user()->id != $id) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
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
            $dataToMerge['departamento_id'] = $this->sanitizeInput($request->input('departamento_id', ''));
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
            'departamento_id' => 'sometimes|required|integer|exists:departamentos,id',
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
            'departamento_id.required' => 'El ID del departamento es obligatorio.',
            'departamento_id.integer' => 'El ID del departamento debe ser un número entero.',
            'departamento_id.exists' => 'El ID del departamento no existe.',
            'rol_id.required' => 'El ID del rol es obligatorio.',
            'rol_id.integer' => 'El ID del rol debe ser un número entero.',
            'rol_id.exists' => 'El ID del rol no existe.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser uno de los siguientes: activo, inactivo, suspendido.',
        ];

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

            if (isset($validatedData['estado'])) {
                $user->estado = $validatedData['estado'];
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

        $user_rol = $this->getUserRole();
        if ($user_rol != 1 || Auth::user()->id == $id) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        try {
            DB::beginTransaction();
            $user = User::where('id', $id)->lockForUpdate()->first();

            if ($user->id == 1) {
                return response()->json([
                    'message' => 'No se puede eliminar el usuario administrador principal',
                    'success' => false
                ], 403);
            }

            if (!$user) {
                return response()->json([
                    'message' => 'No se encontró el usuario',
                    'success' => false
                ], 404);
            }

            $user->delete();
            DB::commit();

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

    public function getByName(Request $request): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user_rol = $this->getUserRole();
        if ($user_rol == 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $name = $this->sanitizeInput($request->nombre);
        $users = Cache::remember('all_users', 60, function () use ($name) {
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

        $user_rol = $this->getUserRole();
        if ($user_rol == 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $users = Cache::remember('all_users', 60, function () use ($role_id) {
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

        $user_rol = $this->getUserRole();
        if ($user_rol == 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $users = Cache::remember('all_users', 60, function () use ($department_id) {
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

        $user_rol = $this->getUserRole();
        if ($user_rol == 6) {
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

        $users = Cache::remember('all_users', 60, function () use ($status) {
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

        $user_rol = $this->getUserRole();
        if ($user_rol == 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $users = Cache::remember('all_users', 60, function () use ($subject_id) {
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

        $user_rol = $this->getUserRole();
        if ($user_rol == 6) {
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

        $user_rol = $this->getUserRole();
        if ($user_rol == 6) {
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

        $user_rol = $this->getUserRole();
        if ($user_rol == 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $users = Cache::remember('docentes', 60, function () {
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

        $user_rol = $this->getUserRole();
        if ($user_rol == 6) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $users = Cache::remember('docentes', 60, function () {
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

    public function getMyProfile(): JsonResponse {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Acceso no autorizado',
                'success' => false
            ], 401);
        }

        $user = (new User())->myProfile(Auth::user()->id)->first();

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
