<?php

namespace App\Models;

use Database\Factories\DepartamentosFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class departamentos extends Model {
    /** @use HasFactory<DepartamentosFactory> */
    use HasFactory;

    /*
     * Almacena los departamentos académicos de la universidad,
     * que sirven como la principal unidad organizativa para materias y docentes
     * */

    protected $table = 'departamentos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
    ];

}
