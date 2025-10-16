<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tipos_notificacion extends Model {
    /** @use HasFactory<\Database\Factories\TiposNotificacionFactory> */
    use HasFactory;

    /*
     * Catálogo que define las plantillas y propiedades de cada tipo de notificación que el sistema puede enviar
     * */

    protected $table = 'tipos_notificacion';

    protected $fillable = [
        'nombre',
        'prioridad',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function notificaciones(): HasMany {
        return $this->hasMany(notificaciones::class, 'tipo_notificacion_id');
    }
}
