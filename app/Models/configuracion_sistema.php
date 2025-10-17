<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class configuracion_sistema extends Model {
    /** @use HasFactory<\Database\Factories\ConfiguracionSistemaFactory> */
    use HasFactory;

    /*
     * Permite a los administradores modificar parámetros y reglas de negocio del sistema directamente desde una interfaz
     * */

    protected $table = 'configuracion_sistema';

    protected $fillable = [
        'clave',
        'valor',
        'tipo_dato',
        'descripcion',
        'categoria',
        'modificable',
        'usuario_identificacion_id',
    ];

    protected $casts = [
        'modificable' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function usuarioModificacion(): BelongsTo {
        return $this->belongsTo(User::class, 'usuario_identificacion_id');
    }
}
