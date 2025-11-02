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
        $this->call(CiclosAcademicosSeeder::class);
        $this->call(CarrerasSeeder::class);
        $this->call(MateriasSeeder::class);

//        $this->call(AulasSeeder::class);
        $this->call(AulasTableSeeder::class);
        $this->call(AulasFotosSeeder::class);
        $this->call(AulasVideosSeeder::class);

        $this->call(RolesSeeder::class);
        $this->call(RecursosTipoSeeder::class);
        $this->call(UserSeeder::class);
    }
}
