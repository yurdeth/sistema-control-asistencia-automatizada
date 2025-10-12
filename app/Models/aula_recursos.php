<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class aula_recursos extends Model {
    /** @use HasFactory<\Database\Factories\AulaRecursosFactory> */
    use HasFactory;

    /*
     * Tabla pivote que establece una relación muchos-a-muchos entre aulas y recursos_tipo,
     * creando un inventario detallado para cada aula
     * */

    protected $table = 'aula_recursos';

    protected $fillable = [
        'aula_id',
        'recurso_tipo_id',
        'cantidad',
        'estado',
        'observaciones'
    ];

    public function getAllAulaRecursos(): Collection {
        return DB::table('aula_recursos')
            ->join('aulas', 'aula_recursos.aula_id', '=', 'aulas.id')
            ->join('recursos_tipos', 'aula_recursos.recurso_tipo_id', '=', 'recursos_tipos.id')
            ->select(
                'recursos_tipos.nombre as recurso_tipo_nombre',
                'aulas.id as id_aula',
                'aulas.nombre as nombre_aula',
                'aula_recursos.cantidad as recurso_cantidad',
                'aula_recursos.id as aula_recurso_id',
                'aula_recursos.estado',
                'aula_recursos.observaciones'
            )
            ->get();
    }

    public function getAllAulaRecursoById(int $resource_id): Collection {
        return $this->getAllAulaRecursos()
            ->where('aula_recurso_id', $resource_id);
    }

    public function getAllAulaRecursoByAulaId(int $aula_id): Collection {
        return $this->getAllAulaRecursos()
            ->where('id_aula', $aula_id);
    }

}
