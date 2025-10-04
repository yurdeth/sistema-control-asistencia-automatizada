<?php

namespace App\Models;

use Database\Factories\AulasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class aulas extends Model {
    /** @use HasFactory<AulasFactory> */
    use HasFactory;

    protected $table = 'aulas';

    protected $fillable = [
        'codigo',
        'nombre',
        'capacidad_pupitres',
        'ubicacion',
        'qr_code',
        'estado'
    ];
}
