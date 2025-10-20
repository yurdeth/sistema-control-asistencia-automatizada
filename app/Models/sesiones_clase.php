<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sesiones_clase extends Model {
    /** @use HasFactory<\Database\Factories\SesionesClaseFactory> */
    use HasFactory;

    /*
     * Cada registro representa una única sesión de clase y almacena los datos reales de inicio y fin
     * */

    protected $table = 'sesiones_clases';

    protected $fillable = [
        'horario_id',
        'fecha_clase',
        'hora_inicio_real',
        'hora_fin_real',
        'duracion_minutos',
        'estado',
        'retraso_minutos',
    ];


    
    public function horario()
    {
        return $this->belongsTo(horarios::class, 'horario_id');
    }

   
    public function asistencias()
    {
        return $this->hasMany(asistencias_estudiantes::class, 'sesion_clase_id');
    }

    
    public function grupo()
    {
        return $this->hasOneThrough(
            grupos::class,
            horarios::class,
            'id',           
            'id',           
            'horario_id',   
            'grupo_id'      
        );
    }


    public function aula()
    {
        return $this->hasOneThrough(
            aulas::class,
            horarios::class,
            'id',           
            'id',           
            'horario_id',   
            'aula_id'       
        );
    }
}
