<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sesiones_clase extends Model {
    /** @use HasFactory<\Database\Factories\SesionesClaseFactory> */
    use HasFactory;

    /*
     * Cada registro representa una única sesión de clase y almacena los datos reales de inicio y fin
     * */

    protected $table = 'sesiones_clase';

    protected $fillable = [
        'horario_id',
        'fecha_clase',
        'hora_inicio_real',
        'hora_fin_real',
        'duracion_minutos',
        'estado',
        'retraso_minutos',
    ];
}
