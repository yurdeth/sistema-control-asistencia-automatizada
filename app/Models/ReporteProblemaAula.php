<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReporteProblemaAula extends Model
{
    protected $table = 'reportes_problemas_aulas';

    protected $fillable = [
        'aula_id',
        'usuario_reporta_id',
        'categoria',
        'descripcion',
        'estado',
        'foto_evidencia',
        'usuario_asignado_id',
        'fecha_resolucion',
        'notas_resolucion'
    ];

    protected $casts = [
        'fecha_resolucion' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relación con el aula donde se reportó el problema
     */
    public function aula(): BelongsTo
    {
        return $this->belongsTo(aulas::class, 'aula_id');
    }

    /**
     * Relación con el usuario que reportó el problema (puede ser docente o estudiante)
     */
    public function usuarioReporta(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_reporta_id');
    }

    /**
     * Relación con el usuario asignado para resolver el problema (opcional)
     */
    public function usuarioAsignado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_asignado_id');
    }
}
