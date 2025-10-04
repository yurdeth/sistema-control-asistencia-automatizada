<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class recursos_tipo extends Model {
    /** @use HasFactory<\Database\Factories\RecursosTipoFactory> */
    use HasFactory;

    protected $table = 'recursos_tipos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'icono'
    ];
}
