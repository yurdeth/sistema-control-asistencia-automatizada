<?php

namespace App\Models;

use Database\Factories\AulasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class aulas extends Model {
    /** @use HasFactory<AulasFactory> */
    use HasFactory;

    /*
     * Registra cada espacio físico que puede ser asignado para actividades académicas
     * */

    protected $table = 'aulas';

    protected $fillable = [
        'codigo',
        'nombre',
        'capacidad_pupitres',
        'ubicacion',
        'qr_code',
        'estado'
    ];

    public function getAulasByCode(string $code) {
        return DB::table('aulas')
            ->where('codigo', 'like', '%' . $code . '%')
            ->get();
    }

    public function getAulasSugeridas(int $capacidad, string $dia_semana, string $hora_inicio, string $hora_fin): Collection {
        return DB::table('aulas')
            ->join('horarios', 'aulas.id', '=', 'horarios.aula_id')
            ->where('aulas.capacidad_pupitres', '>=', $capacidad)
            ->where('horarios.dia_semana', '=', $dia_semana)
            ->where('horarios.hora_inicio', '>=', $hora_inicio)
            ->where('horarios.hora_fin', '<=', $hora_fin)
            ->where('aulas.estado', '=', 'disponible')
            ->select('aulas.*', 'horarios.dia_semana', 'horarios.hora_inicio', 'horarios.hora_fin')
            ->get();
    }
}
