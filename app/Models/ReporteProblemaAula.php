<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    public function getFullReport(): Collection {
        return DB::table( 'reportes_problemas_aulas')
            ->join('aulas', 'reportes_problemas_aulas.aula_id', '=', 'aulas.id')
            ->leftJoin('users', 'reportes_problemas_aulas.usuario_asignado_id', '=', 'users.id')
            ->select(
                'reportes_problemas_aulas.id',
                'reportes_problemas_aulas.categoria',
                'reportes_problemas_aulas.descripcion',
                'reportes_problemas_aulas.estado',
                'reportes_problemas_aulas.foto_evidencia',
                'reportes_problemas_aulas.fecha_resolucion',
                'reportes_problemas_aulas.notas_resolucion',
                'reportes_problemas_aulas.created_at as fecha_reporte',
                'aulas.nombre as aula_nombre',
                'users.nombre_completo as usuario_asignado'
            )
            ->get();
    }

    public function getFullReportByCategory(string $category): Collection {
        return DB::table( 'reportes_problemas_aulas')
            ->join('aulas', 'reportes_problemas_aulas.aula_id', '=', 'aulas.id')
            ->leftJoin('users', 'reportes_problemas_aulas.usuario_asignado_id', '=', 'users.id')
            ->select(
                'reportes_problemas_aulas.id',
                'reportes_problemas_aulas.categoria',
                'reportes_problemas_aulas.descripcion',
                'reportes_problemas_aulas.estado',
                'reportes_problemas_aulas.foto_evidencia',
                'reportes_problemas_aulas.fecha_resolucion',
                'reportes_problemas_aulas.notas_resolucion',
                'reportes_problemas_aulas.created_at as fecha_reporte',
                'aulas.nombre as aula_nombre',
                'users.nombre_completo as usuario_asignado'
            )
            ->where('reportes_problemas_aulas.categoria', $category)
            ->get();
    }

    public function getFullReportByStatus(string $status): Collection {
        return DB::table( 'reportes_problemas_aulas')
            ->join('aulas', 'reportes_problemas_aulas.aula_id', '=', 'aulas.id')
            ->leftJoin('users', 'reportes_problemas_aulas.usuario_asignado_id', '=', 'users.id')
            ->select(
                'reportes_problemas_aulas.id',
                'reportes_problemas_aulas.categoria',
                'reportes_problemas_aulas.descripcion',
                'reportes_problemas_aulas.estado',
                'reportes_problemas_aulas.foto_evidencia',
                'reportes_problemas_aulas.fecha_resolucion',
                'reportes_problemas_aulas.notas_resolucion',
                'reportes_problemas_aulas.created_at as fecha_reporte',
                'aulas.nombre as aula_nombre',
                'users.nombre_completo as usuario_asignado'
            )
            ->where('reportes_problemas_aulas.estado', $status)
            ->get();
    }

    public function getByCategoryAndStatus(string $category, string $status): Collection {
        return DB::table( 'reportes_problemas_aulas')
            ->join('aulas', 'reportes_problemas_aulas.aula_id', '=', 'aulas.id')
            ->leftJoin('users', 'reportes_problemas_aulas.usuario_asignado_id', '=', 'users.id')
            ->select(
                'reportes_problemas_aulas.id',
                'reportes_problemas_aulas.categoria',
                'reportes_problemas_aulas.descripcion',
                'reportes_problemas_aulas.estado',
                'reportes_problemas_aulas.foto_evidencia',
                'reportes_problemas_aulas.fecha_resolucion',
                'reportes_problemas_aulas.notas_resolucion',
                'reportes_problemas_aulas.created_at as fecha_reporte',
                'aulas.nombre as aula_nombre',
                'users.nombre_completo as usuario_asignado'
            )
            ->where('reportes_problemas_aulas.categoria', $category)
            ->where('reportes_problemas_aulas.estado', $status)
            ->get();
    }
}
