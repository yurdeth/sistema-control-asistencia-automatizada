<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable {
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    protected $table = "users";

    protected $fillable = [
        'nombre_completo',
        'email',
        'telefono',
        'password',
        'departamento_id',
        'email_verificado',
        'estado',
        'ultimo_acceso',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = ['password', 'remember_token',];

    public function getAllUsers(): Collection {
        return DB::table('users')
            ->join('usuario_roles', 'users.id', '=', 'usuario_roles.usuario_id')
            ->join('roles', 'usuario_roles.rol_id', '=', 'roles.id')
            ->leftJoin('departamentos', 'users.departamento_id', '=', 'departamentos.id')
            ->leftJoin('carreras', 'users.carrera_id', '=', 'carreras.id')
            ->select(
                'users.id',
                'users.nombre_completo',
                'users.email',
                'users.telefono',
                'users.departamento_id',
                'users.carrera_id',
                'users.estado',
                'usuario_roles.rol_id',
                'roles.nombre as rol_nombre',
                'departamentos.nombre as departamento_nombre',
                'carreras.nombre as carrera_nombre'
            )
            ->get();
    }

    public function getUsersBasedOnMyUserRole(int $my_role_id): Collection {
        $all_users = $this->getAllUsers();

        return match ($my_role_id) {
            1 => $all_users,
            2 => $all_users->where('rol_id', '!=', 1),
            3 => $all_users->whereNotIn('rol_id', [1, 2]),
            4 => $all_users->whereNotIn('rol_id', [1, 2, 3]),
            5 => $all_users->whereNotIn('rol_id', [1, 2, 3, 4]),
            default => collect(),
        };
    }

    public function getUser(int $user_id): Collection {
        return DB::table('users')
            ->join('usuario_roles', 'users.id', '=', 'usuario_roles.usuario_id')
            ->join('roles', 'usuario_roles.rol_id', '=', 'roles.id')
            ->leftJoin('departamentos', 'users.departamento_id', '=', 'departamentos.id')
            ->leftJoin('carreras', 'users.carrera_id', '=', 'carreras.id')
            ->select(
                'users.id',
                'users.nombre_completo',
                'users.email',
                'users.telefono',
                'users.departamento_id',
                'users.carrera_id',
                'users.estado',
                'usuario_roles.rol_id',
                'roles.nombre as rol_nombre',
                'departamentos.nombre as departamento_nombre',
                'carreras.nombre as carrera_nombre'
            )
            ->where('users.id', $user_id)
            ->get();
    }

    public function getUserBasedOnMyUserRole(int $my_role_id, int $user_id): Collection {
        $user = $this->getUser($user_id);

        // Si no se encuentra el usuario, devolver colección vacía
        if ($user->isEmpty()) {
            return collect();
        }

        $user_role_id = $user->first()->rol_id;

        // Verificar si el rol actual puede ver el rol del usuario solicitado
        $puedeVer = match ($my_role_id) {
            1 => true, // ROOT puede ver a todos
            2 => $user_role_id != 1, // Admin puede ver todos excepto ROOT
            3 => !in_array($user_role_id, [1, 2]), // Gestor Departamento puede ver todos excepto ROOT y Admin
            4 => !in_array($user_role_id, [1, 2, 3]), // Gestor Carrera puede ver todos excepto ROOT, Admin y Gestor Departamento
            5 => !in_array($user_role_id, [1, 2, 3, 4]), // Profesor puede ver todos excepto roles administrativos
            6 => $user_id === $user_role_id ? true : ($user_role_id === 6), // Estudiantes pueden verse a sí mismos y otros estudiantes
            default => false, // Otros roles no pueden ver
        };

        return $puedeVer ? $user : collect();
    }

    public function getUsersByRole(int $role_id): Collection {
        return $this->getAllUsers()->where('rol_id', $role_id);
    }

    public function getUsersByDepartment(int $department_id): Collection {
        return $this->getAllUsers()->where('departamento_id', $department_id);
    }

    public function getUsersByStatus($status): Collection {
        return $this->getAllUsers()->where('estado', $status);
    }

    public function getByStatusByRole($status, $role_id): Collection {
        return $this->getAllUsers()
            ->where('estado', $status)
            ->where('rol_id', $role_id);
    }

    public function getByDepartmentByRole($role_id, $department_id): Collection {
        return $this->getAllUsers()
            ->where('departamento_id', $department_id)
            ->where('rol_id', $role_id);
    }

    public function getByCareerByRole($role_id, $carrera_id): Collection {
        return DB::table('users')
            ->join('usuario_roles', 'users.id', '=', 'usuario_roles.usuario_id')
            ->join('roles', 'usuario_roles.rol_id', '=', 'roles.id')
            ->leftJoin('carreras', 'users.carrera_id', '=', 'carreras.id')
            ->select(
                'users.id',
                'users.nombre_completo',
                'users.email',
                'users.telefono',
                'users.carrera_id',
                'users.estado',
                'usuario_roles.rol_id',
                'roles.nombre as rol_nombre',
                'carreras.nombre as carrera_nombre'
            )
            ->where('carrera_id', $carrera_id)
            ->where('rol_id', $role_id)
            ->get();
    }

    public function getUsersBySubject(int $subject_id): Collection {
        return DB::table('users')
            ->join('usuario_roles', 'users.id', '=', 'usuario_roles.usuario_id')
            ->join('roles', 'usuario_roles.rol_id', '=', 'roles.id')
            ->join('departamentos', 'users.departamento_id', '=', 'departamentos.id')
            ->join('materias', 'departamentos.id', '=', 'materias.departamento_id')
            ->where('materias.id', $subject_id)
            ->select(
                'users.id',
                'users.nombre_completo',
                'users.email',
                'users.telefono',
                'users.estado',
                'usuario_roles.rol_id',
                'roles.nombre as rol_nombre',
                'departamentos.nombre as departamento_nombre',
                'materias.nombre as materia_nombre'
            )
            ->get();
    }

    public function getAdministradoresAcademicosOnly(): Collection {
        return DB::table('users')
            ->join('usuario_roles', 'users.id', '=', 'usuario_roles.usuario_id')
            ->join('roles', 'usuario_roles.rol_id', '=', 'roles.id')
            ->leftJoin('departamentos', 'users.departamento_id', '=', 'departamentos.id')
            ->leftJoin('carreras', 'users.carrera_id', '=', 'carreras.id')
            ->select(
                'users.id',
                'users.nombre_completo',
                'users.email',
                'users.telefono',
                'users.departamento_id',
                'users.carrera_id',
                'users.estado',
                'usuario_roles.rol_id',
                'roles.nombre as rol_nombre',
                'departamentos.nombre as departamento_nombre',
                'carreras.nombre as carrera_nombre'
            )
            ->where('roles.nombre', '=', 'administrador_academico')
            ->get();
    }

    public function getDepartmentManagersOnly(): Collection {
        return DB::table('users')
            ->join('usuario_roles', 'users.id', '=', 'usuario_roles.usuario_id')
            ->join('roles', 'usuario_roles.rol_id', '=', 'roles.id')
            ->leftJoin('departamentos', 'users.departamento_id', '=', 'departamentos.id')
            ->leftJoin('carreras', 'users.carrera_id', '=', 'carreras.id')
            ->select(
                'users.id',
                'users.nombre_completo',
                'users.email',
                'users.telefono',
                'users.departamento_id',
                'users.carrera_id',
                'users.estado',
                'usuario_roles.rol_id',
                'roles.nombre as rol_nombre',
                'departamentos.nombre as departamento_nombre',
                'carreras.nombre as carrera_nombre'
            )
            ->where('roles.nombre', '=', 'jefe_departamento')
            ->limit(50)
            ->get();
    }

    public function getCareerManagersOnly(): Collection {
        return DB::table('users')
            ->join('usuario_roles', 'users.id', '=', 'usuario_roles.usuario_id')
            ->join('roles', 'usuario_roles.rol_id', '=', 'roles.id')
            ->leftJoin('departamentos', 'users.departamento_id', '=', 'departamentos.id')
            ->leftJoin('carreras', 'users.carrera_id', '=', 'carreras.id')
            ->select(
                'users.id',
                'users.nombre_completo',
                'users.email',
                'users.telefono',
                'users.departamento_id',
                'users.carrera_id',
                'users.estado',
                'usuario_roles.rol_id',
                'roles.nombre as rol_nombre',
                'departamentos.nombre as departamento_nombre',
                'carreras.nombre as carrera_nombre'
            )
            ->where('roles.nombre', '=', 'coordinador_carreras')
            ->limit(50)
            ->get();
    }

    public function getProfessorsOnly(): Collection {
        return DB::table('users')
            ->join('usuario_roles', 'users.id', '=', 'usuario_roles.usuario_id')
            ->join('roles', 'usuario_roles.rol_id', '=', 'roles.id')
            ->leftJoin('departamentos', 'users.departamento_id', '=', 'departamentos.id')
            ->leftJoin('carreras', 'users.carrera_id', '=', 'carreras.id')
            ->select(
                'users.id',
                'users.nombre_completo',
                'users.email',
                'users.telefono',
                'users.departamento_id',
                'users.carrera_id',
                'users.estado',
                'usuario_roles.rol_id',
                'roles.nombre as rol_nombre',
                'departamentos.nombre as departamento_nombre',
                'carreras.nombre as carrera_nombre'
            )
            ->where('roles.nombre', '=', 'docente')
            ->limit(50)
            ->get();
    }

    public function getStudentsOnly(): Collection {
        return DB::table('users')
            ->join('usuario_roles', 'users.id', '=', 'usuario_roles.usuario_id')
            ->join('roles', 'usuario_roles.rol_id', '=', 'roles.id')
            ->leftJoin('departamentos', 'users.departamento_id', '=', 'departamentos.id')
            ->leftJoin('carreras', 'users.carrera_id', '=', 'carreras.id')
            ->select(
                'users.id',
                'users.nombre_completo',
                'users.email',
                'users.telefono',
                'users.departamento_id',
                'users.carrera_id',
                'users.estado',
                'usuario_roles.rol_id',
                'roles.nombre as rol_nombre',
                'departamentos.nombre as departamento_nombre',
                'carreras.nombre as carrera_nombre'
            )
            ->where('roles.nombre', '=', 'estudiante')
            ->limit(50)
            ->get();
    }

    public function myProfile($user_id): Collection {
        return DB::table('users')
            ->join('usuario_roles', 'users.id', '=', 'usuario_roles.usuario_id')
            ->join('roles', 'usuario_roles.rol_id', '=', 'roles.id')
            ->leftJoin('departamentos', 'users.departamento_id', '=', 'departamentos.id')
            ->leftJoin('carreras', 'users.carrera_id', '=', 'carreras.id')
            ->select(
                'users.id',
                'users.nombre_completo',
                'users.email',
                'users.telefono',
                'users.departamento_id',
                'users.carrera_id',
                'users.estado',
                'usuario_roles.rol_id',
                'roles.nombre as rol_nombre',
                'departamentos.nombre as departamento_nombre',
                'carreras.nombre as carrera_nombre'
            )
            ->where('users.id', '=', $user_id)
            ->get();
    }

    public function getByName(string $name, int $rol_id): Collection {
        return $this->getAllUsers()
            ->where('rol_id', '=', $rol_id)
            ->filter(function ($user) use ($name) {
                return stripos($user->nombre_completo, $name) !== false
                    || stripos($user->email, $name) !== false
                    || stripos($user->telefono, $name) !== false;
            })
            ->values();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed',];
    }
}
