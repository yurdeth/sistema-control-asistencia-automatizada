<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class solicitudes_inscripcion extends Model {
    /** @use HasFactory<\Database\Factories\SolicitudesInscripcionFactory> */
    use HasFactory;

    /*
     * Actúa como un registro transaccional para el proceso de inscripción.
     * Almacena las peticiones de los estudiantes para unirse a un grupo o las invitaciones enviadas por los docentes
     * */

    protected $table = 'solicitudes_inscripcions';

    protected $fillable = [
        'estudiante_id',
        'grupo_id',
        'tipo_solicitud',
        'estado',
        'respondido_por'
    ];
}
