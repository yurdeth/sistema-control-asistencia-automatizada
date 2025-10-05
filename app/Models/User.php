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
        'token_verificacion',
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

    public function getUsers(): Collection {
        return DB::table('users')
            ->join('usuario_roles', 'users.id', '=', 'usuario_roles.usuario_id')
            ->join('roles', 'usuario_roles.rol_id', '=', 'roles.id')
            ->join('departamentos', 'users.departamento_id', '=', 'departamentos.id')
            ->select(
                'users.id',
                'users.nombre_completo',
                'users.email',
                'users.telefono',
                'users.estado',
                'usuario_roles.rol_id',
                'roles.nombre as rol_nombre',
                'departamentos.nombre as departamento_nombre'
            )
            ->get();
    }

    public function getUser($user_id): Collection {
        return $this->getUsers()->where('id', $user_id);
    }
}
