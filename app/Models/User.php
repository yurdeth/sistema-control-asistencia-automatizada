<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed',];
    }

    public function getAllUsers(): Collection {
        return DB::table('users')
            ->join('usuario_roles', 'users.id', '=', 'usuario_roles.usuario_id')
            ->join('roles', 'usuario_roles.rol_id', '=', 'roles.id')
            ->join('departamentos', 'users.departamento_id', '=', 'departamentos.id')
            ->select(
                'users.id',
                'users.nombre_completo',
                'users.email',
                'users.telefono',
                'users.departamento_id',
                'users.estado',
                'usuario_roles.rol_id',
                'roles.nombre as rol_nombre',
                'departamentos.nombre as departamento_nombre'
            )
            ->limit(50)
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
        return $this->getAllUsers()->where('id', $user_id);
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
        return $this->getAllUsers()->where('rol_id', '=', 2);
    }

    public function getDepartmentManagersOnly(): Collection {
        return $this->getAllUsers()->where('rol_id', '=', 3);
    }

    public function getCareerManagersOnly(): Collection {
        return $this->getAllUsers()->where('rol_id', '=', 4);
    }

    public function getProfessorsOnly(): Collection {
        return $this->getAllUsers()->where('rol_id', '=', 5);
    }

    public function getStudentsOnly(): Collection {
        return $this->getAllUsers()->where('rol_id', '=', 6);
    }

    public function myProfile($user_id): Collection {
        return $this->getAllUsers()->where('id', '=', $user_id);
    }

    public function getByName(string $name): Collection {
        return DB::table('users')
            ->where('nombre_completo', 'like', '%' . $name . '%')
            ->get();
    }
}
