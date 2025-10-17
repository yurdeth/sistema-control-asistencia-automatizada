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
        'departamento_id',
        'estado'
    ];

    public function getSubjectsByDepartment(int $department_id): Collection {
        return DB::table('materias')
            ->join('departamentos', 'materias.departamento_id', '=', 'departamentos.id')
            ->select('materias.*', 'departamentos.nombre as departamento_nombre')
            ->where('departamento_id', $department_id)
            ->get();
    }

    public function getSubjectsByStatus(string $estado): Collection {
        return DB::table('materias')
            ->join('departamentos', 'materias.departamento_id', '=', 'departamentos.id')
            ->select('materias.*', 'departamentos.nombre as departamento_nombre')
            ->where('materias.estado', $estado)
            ->get();
    }

    public function getSubjectsByUserId(int $user_id): Collection {
        return DB::table('materias')
            ->join('users_materias', 'materias.id', '=', 'users_materias.materia_id')
            ->join('users', 'users_materias.user_id', '=', 'users.id')
            ->select('materias.*')
            ->where('users.id', $user_id)
            ->get();
    }

    public function getMySubjects(): Collection {
        return DB::table('materias')
            ->join('users_materias', 'materias.id', '=', 'users_materias.materia_id')
            ->join('users', 'users_materias.user_id', '=', 'users.id')
            ->select('materias.*')
            ->where('users.id', Auth::user()->id)
            ->get();
    }
}
