<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MateriasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Leer el archivo JSON
        $jsonPath = database_path('seeders/data/materias.json');

        if (!File::exists($jsonPath)) {
            $this->command->error("El archivo materias.json no existe en: {$jsonPath}");
            return;
        }

        $materiasData = json_decode(File::get($jsonPath), true);

        if (!is_array($materiasData)) {
            $this->command->error("Error al decodificar el JSON de materias");
            return;
        }

        $insertadas = 0;
        $omitidas = 0;
        $carrerasNoEncontradas = [];

        $this->command->info("Iniciando inserción de materias...\n");

        foreach ($materiasData as $materiaData) {
            $codigo = $materiaData['codigo'];
            $nombreMateria = $materiaData['materia'];
            $nombreCarrera = $materiaData['carrera'];

            $carrera = DB::table('carreras')
                ->whereRaw('LOWER(nombre) = ?', [strtolower($nombreCarrera)])
                ->first();

            if (!$carrera) {
                if (!in_array($nombreCarrera, $carrerasNoEncontradas)) {
                    $carrerasNoEncontradas[] = $nombreCarrera;
                }
                $omitidas++;
                continue;
            }

            // Verificar si la materia ya existe para evitar duplicados (mismo código o misma materia + carrera)
            $existe = DB::table('materias')
                ->where(function($query) use ($codigo, $nombreMateria, $carrera) {
                    $query->where('codigo', $codigo)
                          ->orWhere(function($q) use ($nombreMateria, $carrera) {
                              $q->where('nombre', $nombreMateria)
                                ->where('carrera_id', $carrera->id);
                          });
                })
                ->exists();

            if ($existe) {
                $this->command->warn("La materia '{$nombreMateria}' ya existe en '{$nombreCarrera}'");
                $omitidas++;
                continue;
            }

            DB::table('materias')->insert([
                'codigo' => $codigo,
                'nombre' => $nombreMateria,
                'descripcion' => null,
                'carrera_id' => $carrera->id,
                'estado' => 'activa',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $insertadas++;
            $this->command->info("[{$codigo}] {$nombreMateria} ({$nombreCarrera})");
        }

        $this->command->info("\n" . str_repeat('=', 60));
        $this->command->info("RESUMEN DE INSERCIÓN");
        $this->command->info(str_repeat('=', 60));
        $this->command->info("Materias insertadas: {$insertadas}");
        $this->command->info("Materias omitidas: {$omitidas}");

        if (!empty($carrerasNoEncontradas)) {
            $this->command->warn("\nCarreras no encontradas:");
            foreach ($carrerasNoEncontradas as $carrera) {
                $this->command->warn("   - {$carrera}");
            }
            $this->command->info("\nTip: Asegúrate de que estas carreras existan en el CarrerasSeeder");
        }

        $this->command->info(str_repeat('=', 60) . "\n");
    }
}
