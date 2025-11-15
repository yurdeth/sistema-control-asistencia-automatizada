<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class escaneos_qr extends Model
{
    use HasFactory;

    protected $table = 'escaneos_qrs';

    protected $fillable = [
        'aula_id',
        'usuario_id',
        'sesion_clase_id',
        'tipo_escaneo',
        'resultado',
        'motivo_fallo',
        'ip_address',
        'dispositivo'
    ];



    public function aula()
    {
        return $this->belongsTo(aulas::class, 'aula_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function sesionClase()
    {
        return $this->belongsTo(sesiones_clase::class, 'sesion_clase_id');
    }
}
