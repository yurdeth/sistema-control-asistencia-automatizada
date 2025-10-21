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
        'indicaciones',
        'latitud',
        'longitud',
        'qr_code',
        'estado'
    ];

    public function getAulasByCode(string $code): Collection {
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

    public function getAll(): Collection {
        return DB::table('aulas')
            ->leftJoin('aula_recursos', 'aulas.id', '=', 'aula_recursos.aula_id')
            ->leftJoin('recursos_tipos', 'aula_recursos.recurso_tipo_id', '=', 'recursos_tipos.id')
            ->select(
                'aulas.id as aula_id',
                'aulas.codigo as codigo_aula',
                'aulas.nombre as nombre_aula',
                'aulas.capacidad_pupitres as capacidad_pupitres',
                'aulas.ubicacion as ubicacion_aula',
                'aulas.qr_code as qr_code',
                'aulas.estado as estado_aula',
                'aulas.created_at as created_at',
                'aulas.updated_at as updated_at',
                'recursos_tipos.nombre as recurso_tipo_nombre',
                'recursos_tipos.descripcion as recurso_tipo_descripcion',
                'aula_recursos.cantidad as recurso_cantidad',
                'aula_recursos.estado as estado_recurso',
                'aula_recursos.observaciones as observaciones_recurso'
            )
            ->get();
    }

    public function getAllById($id) {
        return $this->getAll()
            ->where('aula_id', $id);
    }

    /**
     * Relación con AulaFoto (hasMany)
     */
    public function fotos()
    {
        return $this->hasMany(AulaFoto::class, 'aula_id');
    }

    /**
     * Relación con AulaVideo (hasMany)
     */
    public function videos()
    {
        return $this->hasMany(AulaVideo::class, 'aula_id');
    }

}
