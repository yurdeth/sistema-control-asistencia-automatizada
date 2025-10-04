<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

}
