<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class system_logs extends Model {
    /** @use HasFactory<\Database\Factories\SystemLogsFactory> */
    use HasFactory;

    /*
     * Registro centralizado para eventos importantes, advertencias y errores del sistema,
     * destinado a los administradores y desarrolladores para monitoreo y depuración
     * */

    protected $table = 'system_logs';

    protected $fillable = [
        'nivel',
        'modulo',
        'accion',
        'usuario_id',
        'contexto',
    ];
}
