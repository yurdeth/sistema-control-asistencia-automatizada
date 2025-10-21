<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AulaFoto extends Model
{
    protected $table = 'aula_fotos';

    protected $fillable = [
        'aula_id',
        'ruta',
    ];

    /**
     * Relación con Aula (belongsTo)
     */
    public function aula()
    {
        return $this->belongsTo(Aula::class, 'aula_id');
    }
}
