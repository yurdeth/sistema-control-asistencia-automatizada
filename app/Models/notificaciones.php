<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class notificaciones extends Model {
    /** @use HasFactory<\Database\Factories\NotificacionesFactory> */
    use HasFactory;

    /*
     * Cola de envío y registro histórico de todas las notificaciones enviadas o por enviar a los usuarios
     * */

    protected $fillable = [
        'tipo_notificacion_id',
        'usuario_destino_id',
        'titulo',
        'mensaje',
        'canal',
        'estado',
    ];
}
