<?php

namespace App\Models;

use Database\Factories\AulasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class aulas extends Model {
    /** @use HasFactory<AulasFactory> */
    use HasFactory;

    /*
     * Registra cada espacio físico que puede ser asignado para actividades académicas
     * */

    protected $table = 'aulas';

    protected $fillable = [
        'codigo',
        'nombre',
        'capacidad_pupitres',
        'ubicacion',
        'qr_code',
        'estado'
    ];
}
