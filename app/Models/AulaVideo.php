<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AulaVideo extends Model
{
    protected $table = 'aula_videos';

    protected $fillable = [
        'aula_id',
        'url',
    ];

    /**
     * Relación con Aula (belongsTo)
     */
    public function aula()
    {
        return $this->belongsTo(Aula::class, 'aula_id');
    }
}
