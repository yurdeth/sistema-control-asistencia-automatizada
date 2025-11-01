<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CiclosAcademicosSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        DB::table("ciclos_academicos")->insert([
            [
                'nombre' => 'Ciclo I-2025',
                'anio' => 2025,
                'numero_ciclo' => 1,
                'fecha_inicio' => '2025-02-01',
                'fecha_fin' => '2025-06-30',
                'estado' => 'finalizado',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Ciclo II-2025',
                'anio' => 2025,
                'numero_ciclo' => 2,
                'fecha_inicio' => '2025-08-01',
                'fecha_fin' => '2025-12-15',
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
