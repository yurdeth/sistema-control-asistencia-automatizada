<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder {
    /**
     * Seed the application's database.
     */
    public function run(): void {
        $this->call(DepartamentosSeeder::class);
        $this->call(CarrerasSeeder::class);

        // Seeder de aulas desde JSON con descarga de imágenes de Firebase
        $this->call(AulasSeeder::class);

        // Seeder antiguo de aulas (comentado - descomenta si prefieres usar este)
        // $this->call(InsercionAulas::class);

        $this->call(RolesSeeder::class);
        $this->call(RecursosTipoSeeder::class);
        $this->call(UserSeeder::class);
    }
}
