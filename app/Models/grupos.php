<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class grupos extends Model {
    /** @use HasFactory<\Database\Factories\GruposFactory> */
    use HasFactory;

    /*
     * Representa una instancia específica de una materia que se imparte
     * en un ciclo determinado, con un docente y un cupo asignados
     * */

    protected $table = 'grupos';

    protected $fillable = [
        'materia_id',
        'ciclo_id',
        'docente_id',
        'numero_grupo',
        'capacidad_maxima',
        'estudiantes_inscrito',
        'estado',
    ];
}
