<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class recursos_tipo extends Model {
    /** @use HasFactory<\Database\Factories\RecursosTipoFactory> */
    use HasFactory;

    /*
     * Catálogo de todos los tipos de equipamiento que puede tener un aula
     * */

    protected $table = 'recursos_tipos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'icono'
    ];
}
