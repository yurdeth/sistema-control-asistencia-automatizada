<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AulaQr extends Model {
    protected $table = 'aula_qrs';

    protected $fillable = [
        'aula_id',
        'ruta',
    ];

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