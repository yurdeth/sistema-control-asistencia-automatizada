<?php

namespace App\Models;

use Database\Factories\CiclosAcademicosFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ciclos_academicos extends Model {
    /** @use HasFactory<CiclosAcademicosFactory> */
    use HasFactory;

    /*
     * Define los periodos en los que se organizan las actividades
     * */

    protected $table = 'ciclos_academicos';

    protected $fillable = [
        'nombre',
        'anio',
        'numero_ciclo',
        'fecha_inicio',
        'fecha_fin',
        'estado',
    ];
    public function grupos()
    {
        return $this->hasMany(Grupo::class, 'ciclo_id');
    }
}
