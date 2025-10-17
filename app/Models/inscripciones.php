<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class inscripciones extends Model {
    /** @use HasFactory<\Database\Factories\InscripcionesFactory> */
    use HasFactory;

    /*
     * Representa la matrícula oficial y activa de un estudiante en un grupo
     * */

    protected $table = 'inscripciones';

    protected $fillable = [
        'estudiante_id',
        'grupo_id',
        'estado',
    ];

     public function estudiante()
    {
        return $this->belongsTo(User::class, 'estudiante_id','id');
    }

    public function grupo()
    {
        return $this->belongsTo(grupos::class, 'grupo_id','id');
    }

}
