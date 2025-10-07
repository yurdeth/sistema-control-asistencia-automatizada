<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class asistencias_estudiantes extends Model {
    /** @use HasFactory<\Database\Factories\AsistenciasEstudiantesFactory> */
    use HasFactory;

    /*
     * Planifica y registra los períodos en que un aula está fuera de servicio por mantenimiento.
     * */

    protected $table = 'asistencias_estudiantes';

    protected $fillable = [
        'sesion_clase_id',
        'estudiante_id',
        'hora_registro',
        'estado',
        'validado_por_qr',
    ];
}
