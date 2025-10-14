<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class horarios extends Model {
    /** @use HasFactory<\Database\Factories\HorariosFactory> */
    use HasFactory;

    /*
     * Define la planificación semanal de un grupo,
     * especificando el día, la hora y el aula para cada bloque de clase
     * */

    protected $table = 'horarios';

    protected $fillable = [
        'grupo_id',
        'aula_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
    ];

    private function horarioBaseQuery()
    {
        return DB::table('horarios')
            ->join('grupos', 'horarios.grupo_id', '=', 'grupos.id')
            ->join('materias', 'grupos.materia_id', '=', 'materias.id')
            ->join('users', 'grupos.docente_id', '=', 'users.id')
            ->join('aulas', 'horarios.aula_id', '=', 'aulas.id')
            ->select(
                'aulas.id as aula_id',
                'horarios.id',
                'horarios.dia_semana',
                'horarios.hora_inicio',
                'horarios.hora_fin',
                'horarios.grupo_id',
                'grupos.numero_grupo as numero_grupo',
                'materias.id as materia_id',
                'materias.codigo as materia_codigo',
                'materias.nombre as materia_nombre',
                'users.id as docente_id',
                'users.nombre_completo as nombre_docente'
            );
    }

    /**
     * Obtiene horarios con límite y paginación
     */
    public function getHorarios(): Collection {
        return $this->horarioBaseQuery()
            ->orderBy('horarios.dia_semana')
            ->orderBy('horarios.hora_inicio')
            ->limit(50)
            ->get();
    }

    public function getHorariosById(int $id): Collection {
        return $this->horarioBaseQuery()
            ->orderBy('horarios.dia_semana')
            ->orderBy('horarios.hora_inicio')
            ->limit(50)
            ->where('horarios.id', $id)
            ->get();
    }

    /**
     * Obtiene horarios del profesor autenticado
     */
    public function getHorariosByProfessor(?int $professor_id = null): Collection {
        $professorId = $professor_id ?? Auth::id();

        if (!$professorId) {
            return collect(); // Retorna colección vacía si no hay usuario autenticado
        }

        return $this->horarioBaseQuery()
            ->where('users.id', $professorId)
            ->orderBy('horarios.dia_semana')
            ->orderBy('horarios.hora_inicio')
            ->get();
    }

    /**
     * Obtiene horarios por ID de profesor (con validación)
     */
    public function getHorariosByProfessorId(int $professor_id): Collection {
        return $this->horarioBaseQuery()
            ->where('users.id', $professor_id)
            ->orderBy('horarios.dia_semana')
            ->orderBy('horarios.hora_inicio')
            ->get();
    }

    /**
     * Obtiene horarios por día de la semana
     */
    public function getHorariosByDay(string $day): Collection {
        $validDays = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'];

        $day = ucfirst(strtolower($day));
        return $this->horarioBaseQuery()
            ->whereIn('horarios.dia_semana', $validDays)
            ->where('horarios.dia_semana', $day)
            ->orderBy('horarios.hora_inicio')
            ->limit(20)
            ->get();
    }

    /**
     * Obtiene horarios por materia
     */
    public function getHorariosByMateria(int $materia_id): Collection {
        return $this->horarioBaseQuery()
            ->where('materias.id', $materia_id)
            ->orderBy('horarios.dia_semana')
            ->orderBy('horarios.hora_inicio')
            ->get();
    }

    public function getHorariosByGroup(int $grupo_id): Collection {
        return $this->horarioBaseQuery()
            ->where('grupos.id', $grupo_id)
            ->orderBy('horarios.dia_semana')
            ->orderBy('horarios.hora_inicio')
            ->get();
    }

    public function getHorariosByClassroom(int $classroom_id): Collection {
        return $this->horarioBaseQuery()
            ->where('aulas.id', $classroom_id)
            ->orderBy('horarios.dia_semana')
            ->orderBy('horarios.hora_inicio')
            ->get();
    }

    public function getHorariosByRango(string $start_time, string $end_time): Collection {
        return $this->horarioBaseQuery()
            ->where('horarios.hora_inicio', '>=', $start_time)
            ->where('horarios.hora_fin', '<=', $end_time)
            ->orderBy('horarios.dia_semana')
            ->orderBy('horarios.hora_inicio')
            ->get();
    }

    public function grupo()
    {
        return $this->belongsTo(grupos::class, 'grupo_id', 'id');
    }

    public function aula()
    {
        return $this->belongsTo(aulas::class, 'aula_id', 'id');
    }

    public function sesiones()
    {
        return $this->hasMany(sesiones_clase::class, 'horario_id', 'id');
    }
}
