<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class aula_recursos extends Model {
    /** @use HasFactory<\Database\Factories\AulaRecursosFactory> */
    use HasFactory;

    protected $table = 'aula_recursos';

    protected $fillable = [
        'aula_id',
        'recurso_tipo_id',
        'cantidad',
        'estado',
        'observaciones'
    ];

}
