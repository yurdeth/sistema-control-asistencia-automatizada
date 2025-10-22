<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class carreras extends Model {
    use HasFactory;

    protected $table = 'carreras';

    protected $fillable = [
        'nombre',
        'departamento_id',
        'estado',
    ];

    public function departamento() {
        return $this->belongsTo(departamentos::class, 'departamento_id');
    }

    public function getAllCarreras(): Collection {
        return DB::table('carreras')
            ->select('id', 'nombre', 'departamento_id')
            ->orderBy('nombre', 'asc')
            ->get();
    }

    public function getCarreraById(int $carrera_id): ?object {
        return DB::table('carreras')
            ->select('id', 'nombre', 'departamento_id')
            ->where('id', $carrera_id)
            ->first();
    }

    public function getByStatus(string $estado): Collection {
        return DB::table('carreras')
            ->select('id', 'nombre', 'departamento_id')
            ->where('estado', $estado)
            ->orderBy('nombre', 'asc')
            ->get();
    }
}
