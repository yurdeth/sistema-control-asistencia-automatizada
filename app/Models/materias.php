<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
