<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class asistencias_estudiantes extends Model 
{
    use HasFactory;

    protected $table = 'asistencias_estudiantes';

    protected $fillable = [
        'sesion_clase_id',
        'estudiante_id',
        'hora_registro',
        'estado',
        'validado_por_qr',
    ];

    public function sesionClase()
    {
        return $this->belongsTo(sesiones_clase::class, 'sesion_clase_id');
    }

  
    public function estudiante()
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }
}