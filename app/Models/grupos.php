<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class grupos extends Model {
    /** @use HasFactory<\Database\Factories\GruposFactory> */
    use HasFactory;

    /*
     * Representa una instancia específica de una materia que se imparte
     * en un ciclo determinado, con un docente y un cupo asignados
     * */

    protected $table = 'grupos';

    protected $fillable = [
        'materia_id',
        'ciclo_id',
        'docente_id',
        'numero_grupo',
        'capacidad_maxima',
        'estudiantes_inscrito',
        'estado',
    ];

    public function getGruposByMateria($materiaId): Collection {
        return DB::table("grupos")
            ->join('materias', 'grupos.materia_id', '=', 'materias.id')
            ->join('users', 'grupos.docente_id', '=', 'users.id')
            ->join('ciclos_academicos', 'grupos.ciclo_id', '=', 'ciclos_academicos.id')
            ->select(
                'grupos.id',
                'grupos.ciclo_id',
                'ciclos_academicos.nombre as nombre_ciclo',
                'grupos.numero_grupo',
                'grupos.capacidad_maxima',
                'grupos.estudiantes_inscrito',
                'grupos.estado',
                'grupos.materia_id as id_materia',
                'materias.nombre as nombre_materia',
                'grupos.docente_id as id_docente',
                'users.nombre_completo as nombre_docente'
            )
            ->where('grupos.materia_id', $materiaId)
            ->get();
    }

    public function getGruposByCiclo($cicloId): Collection {
        return DB::table("grupos")
            ->join('materias', 'grupos.materia_id', '=', 'materias.id')
            ->join('users', 'grupos.docente_id', '=', 'users.id')
            ->join('ciclos_academicos', 'grupos.ciclo_id', '=', 'ciclos_academicos.id')
            ->select(
                'grupos.id',
                'grupos.ciclo_id',
                'ciclos_academicos.nombre as nombre_ciclo',
                'grupos.numero_grupo',
                'grupos.capacidad_maxima',
                'grupos.estudiantes_inscrito',
                'grupos.estado',
                'grupos.materia_id as id_materia',
                'materias.nombre as nombre_materia',
                'grupos.docente_id as id_docente',
                'users.nombre_completo as nombre_docente'
            )
            ->where('grupos.ciclo_id', $cicloId)
            ->get();
    }

    public function getGruposByDocente($docenteId): Collection {
        return DB::table("grupos")
            ->join('materias', 'grupos.materia_id', '=', 'materias.id')
            ->join('users', 'grupos.docente_id', '=', 'users.id')
            ->join('ciclos_academicos', 'grupos.ciclo_id', '=', 'ciclos_academicos.id')
            ->select(
                'grupos.id',
                'grupos.ciclo_id',
                'ciclos_academicos.nombre as nombre_ciclo',
                'grupos.numero_grupo',
                'grupos.capacidad_maxima',
                'grupos.estudiantes_inscrito',
                'grupos.estado',
                'grupos.materia_id as id_materia',
                'materias.nombre as nombre_materia',
                'grupos.docente_id as id_docente',
                'users.nombre_completo as nombre_docente'
            )
            ->where('grupos.docente_id', $docenteId)
            ->get();
    }

    public function getGruposByEstado($estado): Collection {
        return DB::table("grupos")
            ->join('materias', 'grupos.materia_id', '=', 'materias.id')
            ->join('users', 'grupos.docente_id', '=', 'users.id')
            ->join('ciclos_academicos', 'grupos.ciclo_id', '=', 'ciclos_academicos.id')
            ->select(
                'grupos.id',
                'grupos.ciclo_id',
                'ciclos_academicos.nombre as nombre_ciclo',
                'grupos.numero_grupo',
                'grupos.capacidad_maxima',
                'grupos.estudiantes_inscrito',
                'grupos.estado',
                'grupos.materia_id as id_materia',
                'materias.nombre as nombre_materia',
                'grupos.docente_id as id_docente',
                'users.nombre_completo as nombre_docente'
            )
            ->where('grupos.estado', $estado)
            ->get();
    }

    public function getGruposDisponibles(): Collection {
        return DB::table("grupos")
            ->join('materias', 'grupos.materia_id', '=', 'materias.id')
            ->join('users', 'grupos.docente_id', '=', 'users.id')
            ->join('ciclos_academicos', 'grupos.ciclo_id', '=', 'ciclos_academicos.id')
            ->select(
                'grupos.id',
                'grupos.ciclo_id',
                'ciclos_academicos.nombre as nombre_ciclo',
                'grupos.numero_grupo',
                'grupos.capacidad_maxima',
                'grupos.estudiantes_inscrito',
                'grupos.estado',
                'grupos.materia_id as id_materia',
                'materias.nombre as nombre_materia',
                'grupos.docente_id as id_docente',
                'users.nombre_completo as nombre_docente'
            )
            ->where('grupos.estado', '=', 'activo')
            ->get();
    }

    public function getGroupProfessorBySubject($grupoId, $materiaId): ?object {
        return DB::table("grupos")
            ->join('users', 'grupos.docente_id', '=', 'users.id')
            ->join('materias', 'grupos.materia_id', '=', 'materias.id')
            ->select(
                'users.id as id',
                'users.nombre_completo as nombre_docente',
            )
            ->where('grupos.id', $grupoId)
            ->where('grupos.materia_id', $materiaId)
            ->first();
    }

    public function materia()
    {
        return $this->belongsTo(materias::class, 'materia_id');
    }

    public function docente()
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    public function ciclo()
    {
        return $this->belongsTo(ciclos_academicos::class, 'ciclo_id');
    }
}
