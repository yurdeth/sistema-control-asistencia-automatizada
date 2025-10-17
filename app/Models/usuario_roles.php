<?php

namespace App\Models;

use Database\Factories\UsuarioRolesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class usuario_roles extends Model {
    /** @use HasFactory<UsuarioRolesFactory> */
    use HasFactory;

    /*
     * Tabla pivote que implementa una relación muchos-a-muchos entre usuarios y roles.
     * Permite que un usuario tenga múltiples roles simultáneamente
     * */

    protected $table = "usuario_roles";

    protected $fillable = [
        'usuario_id',
        'rol_id',
        'asignado_por_id',
    ];

}
