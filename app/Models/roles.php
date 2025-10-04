<?php

namespace App\Models;

use Database\Factories\RolesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class roles extends Model {
    /** @use HasFactory<RolesFactory> */
    use HasFactory;

    /*
     * Catálogo de los roles disponibles en el sistema para la gestión de permisos
     * */

    protected $table = 'roles';

    protected $fillable = [
        'id',
        'nombre',
        'descripcion',
    ];
}
