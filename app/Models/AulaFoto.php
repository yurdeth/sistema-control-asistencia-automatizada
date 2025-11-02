<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AulaFoto extends Model {
    protected $table = 'aula_fotos';

    protected $fillable = [
        'aula_id',
        'ruta',
    ];

    // En app/Models/AulaFoto.php
    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        // Usa la URL configurada en .env
        return config('app.url') . '/storage/' . $this->ruta;
    }

    /**
     * Relación con Aula (belongsTo)
     */
    public function aula() {
        return $this->belongsTo(Aula::class, 'aula_id');
    }
}
