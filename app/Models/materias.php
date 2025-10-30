<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class materias extends Model {
    /** @use HasFactory<\Database\Factories\MateriasFactory> */
    use HasFactory;

    /*
     * Funciona como un catálogo central de todas las asignaturas que la universidad puede impartir
     * */

    protected $table = "materias";

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'carrera_id',
        'estado'
    ];

    public function getSubjectsByStatus(string $estado): Collection {
        return DB::table('materias')
            ->join('carreras', 'materias.carrera_id', '=', 'carreras.id')
            ->select('materias.*', 'carreras.nombre as carrera_nombre')
            ->where('materias.estado', $estado)
            ->get();
    }

    public function getSubjectsByCareerId(int $career_id): Collection {
        return DB::table('materias')
            ->join('carreras', 'materias.carrera_id', '=', 'carreras.id')
            ->select(
                'materias.id as id_materia',
                'materias.codigo as codigo_materia',
                'materias.nombre as nombre_materia',
                'materias.carrera_id',
                'carreras.nombre as nombre_carrera'
            )
            ->where('carreras.id', $career_id)
            ->get();
    }

    public function getSubjectsByUserId(int $user_id): Collection {
        return DB::table('materias')
            ->join('carreras', 'materias.carrera_id', '=', 'carreras.id')
            ->join('users', 'carreras.id', '=', 'users.carrera_id')
            ->select(
                'materias.id as id_materia',
                'materias.codigo as codigo_materia',
                'materias.nombre as nombre_materia',
                'materias.carrera_id',
                'carreras.nombre as nombre_carrera'
            )
            ->where('users.id', $user_id)
            ->get();
    }

    public function getMySubjects(): Collection {
        return DB::table('materias')
            ->join('carreras', 'materias.carrera_id', '=', 'carreras.id')
            ->join('users', 'carreras.id', '=', 'users.carrera_id')
            ->select(
                'materias.id as id_materia',
                'materias.codigo as codigo_materia',
                'materias.nombre as nombre_materia',
                'materias.carrera_id',
                'carreras.nombre as nombre_carrera'
            )
            ->where('users.id', Auth::id())
            ->get();
    }
}
