<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class estadisticas_aulas_diarias extends Model {
    /** @use HasFactory<\Database\Factories\EstadisticasAulasDiariasFactory> */
    use HasFactory;

    /*
     * Tabla de agregación para optimizar el rendimiento de los reportes
     * */

    protected $table = 'estadisticas_aulas_diarias';

    protected $fillable = [
        'aula_id',
        'fecha',
        'minutos_ocupada',
        'porcentaje_ocupacion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'minutos_ocupada' => 'integer',
        'porcentaje_ocupacion' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function aula(): BelongsTo {
        return $this->belongsTo(Aula::class, 'aula_id');
    }
}
