<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecursosTipoSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        DB::table('recursos_tipos')->insert([
            [
                'nombre' => 'Pantalla táctil',
                'descripcion' => 'Dispositivos con pantalla táctil',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Proyector',
                'descripcion' => 'Dispositivos de proyección de imágenes o videos',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Aire acondicionado',
                'descripcion' => 'Dispositivos para controlar la temperatura del ambiente',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Ventiladores',
                'descripcion' => 'Dispositivos para mejorar la circulación del aire',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
