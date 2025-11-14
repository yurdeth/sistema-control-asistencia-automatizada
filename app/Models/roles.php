<?php

namespace App\Models;

use Database\Factories\RolesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class roles extends Model {
    /** @use HasFactory<RolesFactory> */
    use HasFactory;

    /*
     * Catálogo de los roles disponibles en el sistema para la gestión de permisos
     * */

    protected $table = 'roles';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    public function getUsersByRole(int $roleId): Collection {
        return DB::table('users')
            ->join('usuario_roles', 'users.id', '=', 'usuario_roles.usuario_id')
            ->leftJoin('departamentos', 'users.departamento_id', '=', 'departamentos.id')
            ->leftJoin('carreras', 'users.carrera_id', '=', 'carreras.id')
            ->where('usuario_roles.rol_id', $roleId)
            ->select(
                'users.nombre_completo',
                'users.email',
                'users.telefono',
                'departamentos.nombre as nombre_departamento',
                'carreras.nombre as nombre_carrera',
                'users.estado'
            )
            ->get();
    }
}
