<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CarrerasSeeder extends Seeder
{
    public function run(): void
    {
        // Verificar si ya existe la carrera para no duplicar
        $existe = DB::table('carreras')->where('nombre', 'Ingeniería de Sistemas Informáticos')->exists();
        
        if (!$existe) {
            DB::table('carreras')->insert([
                'nombre' => 'Ingeniería de Sistemas Informáticos',
                'departamento_id' => 1, // Ajusta este ID según tu departamento existente
                'estado' => 'activa',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            
            $this->command->info('Carrera creada exitosamente!');
        } else {
            $this->command->info('La carrera ya existe.');
        }
    }
}