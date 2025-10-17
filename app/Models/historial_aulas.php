<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class historial_aulas extends Model {
    /** @use HasFactory<\Database\Factories\HistorialAulasFactory> */
    use HasFactory;

    /*
     * Guarda un registro inmutable de cada cambio realizado en los datos de un aula.
     * En esta tabla no se realizan registros manuales, sino que todos los cambios se hacen desde un trigger
     * */

    protected $table = 'historial_aulas';

    protected $fillable = [
        'aula_id',
        'usuario_modificacion_id',
        'campo_modificado',
        'valor_anterior',
        'valor_nuevo',
        'tipo_operacion',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function aula(): BelongsTo {
        return $this->belongsTo(Aula::class, 'aula_id');
    }

    public function usuarioModificacion(): BelongsTo {
        return $this->belongsTo(User::class, 'usuario_modificacion_id');
    }
}