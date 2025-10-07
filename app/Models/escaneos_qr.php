<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class escaneos_qr extends Model {
    /** @use HasFactory<\Database\Factories\EscaneosQrFactory> */
    use HasFactory;

    /*
     * Registra cada intento de escaneo de un código QR, sea exitoso o no
     * */

    protected $table = 'escaneos_qr';

    protected $fillable = [
        'aula_id',
        'usuario_id',
        'sesion_clase_id',
        'tipo_escaneo',
        'resultado',
        'motivo_fallo',
        'ip_address',
    ];
}
