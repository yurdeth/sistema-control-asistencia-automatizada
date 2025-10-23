<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AulasTableSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/aulas.json');

        if (!file_exists($jsonPath)) {
            $this->command->error("❌ aulas.json no encontrado");
            return;
        }

        $aulas = json_decode(file_get_contents($jsonPath), true);

        if (!$aulas) {
            $this->command->error('❌ Error al leer aulas.json');
            return;
        }

        $this->command->info("📦 Insertando " . count($aulas) . " aulas...");

        foreach ($aulas as $aula) {
            DB::table('aulas')->insert([
                'id' => $aula['id'],
                'codigo' => $aula['codigo'],
                'nombre' => $aula['nombre'],
                'capacidad_pupitres' => $aula['capacidad_pupitres'],
                'ubicacion' => $aula['ubicacion'],
                'indicaciones' => $aula['indicaciones'],
                'latitud' => $aula['latitud'],
                'longitud' => $aula['longitud'],
                'qr_code' => $aula['qr_code'],
                'estado' => $aula['estado'],
                'created_at' => $aula['created_at'],
                'updated_at' => $aula['updated_at'],
            ]);
        }

        $this->command->info('✅ Aulas insertadas');
    }
}
