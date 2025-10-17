<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class mantenimientos extends Model {
    /** @use HasFactory<\Database\Factories\MantenimientosFactory> */
    use HasFactory;

    /*
     * Planifica y registra los períodos en que un aula está fuera de servicio por mantenimiento.
     * */

    protected $table = 'mantenimientos';

    protected $fillable = [
        'aula_id',
        'usuario_registro_id',
        'motivo',
        'fecha_inicio',
        'fecha_fin_programada',
        'fecha_fin_real',
        'estado',
    ];

    public function aula()
    {
        return $this->belongsTo(aulas::class, 'aula_id');
    }

    
    public function usuarioRegistro()
    {
        return $this->belongsTo(User::class, 'usuario_registro_id');
    }
}
