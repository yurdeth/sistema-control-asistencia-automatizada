<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class horarios extends Model {
    /** @use HasFactory<\Database\Factories\HorariosFactory> */
    use HasFactory;

    /*
     * Define la planificación semanal de un grupo,
     * especificando el día, la hora y el aula para cada bloque de clase
     * */

    protected $table = 'horarios';

    protected $fillable = [
        'grupo_id',
        'aula_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
    ];

}
