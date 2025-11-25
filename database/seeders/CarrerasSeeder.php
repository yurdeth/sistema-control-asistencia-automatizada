<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CarrerasSeeder extends Seeder {
    public function run(): void {
        // Verificar si ya existe la carrera para no duplicar
        /*$existe = DB::table('carreras')->where('nombre', 'Ingeniería de Sistemas Informáticos')->exists();

        if (!$existe) {
            DB::table('carreras')->insert([
                    ['nombre' => 'Ingeniería de Sistemas Informáticos', 'departamento_id' => 1, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Ingeniería Eléctrica', 'departamento_id' => 1, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Ingeniería Mecánica', 'departamento_id' => 1, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Arquitectura', 'departamento_id' => 1, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Ingeniería Industrial', 'departamento_id' => 1, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Laboratorio Clínico', 'departamento_id' => 2, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Doctorado en Medicina', 'departamento_id' => 2, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Anestesiología e Inhaloterapia', 'departamento_id' => 2, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Fisioterapia y terapia ocupacional', 'departamento_id' => 2, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Licenciatura en Inglés', 'departamento_id' => 3, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Licenciatura en Psicología', 'departamento_id' => 3, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Licenciatura en Educación', 'departamento_id' => 3, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Licenciatura en Sociología', 'departamento_id' => 3, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Licenciatura en Letras', 'departamento_id' => 3, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Licenciatura en Filosofía', 'departamento_id' => 3, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Ingeniería Agronómica', 'departamento_id' => 4, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Administración', 'departamento_id' => 5, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Contaduría Pública', 'departamento_id' => 5, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Ciencias Económicas', 'departamento_id' => 5, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Licenciatura en Ciencias Jurídicas', 'departamento_id' => 6, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Licenciatura en Biología', 'departamento_id' => 7, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Licenciatura en Matemática', 'departamento_id' => 7, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Licenciatura en Estadística', 'departamento_id' => 7, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Licenciatura en Física', 'departamento_id' => 7, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Profesorado en Ciencias Naturales', 'departamento_id' => 7, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Profesorado en Matemática', 'departamento_id' => 7, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Licenciatura en Química', 'departamento_id' => 7, 'created_at' => now(), 'updated_at' => now()],
                    ['nombre' => 'Licenciatura en Química y Farmacia', 'departamento_id' => 8, 'created_at' => now(), 'updated_at' => now()],
                ]
            );
        }*/

        // Leer el archivo JSON de carreras
        $jsonPath = database_path('seeders/data/carreras.json');

        if (!File::exists($jsonPath)) {
            $this->command->error("❌ El archivo carreras.json no existe en: {$jsonPath}");
            return;
        }

        $carrerasData = json_decode(File::get($jsonPath), true);

        if (!is_array($carrerasData)) {
            $this->command->error("Error al decodificar el JSON de carreras");
            return;
        }

        $insertadas = 0;
        $omitidas = 0;
        $departamentosNoEncontrados = [];

        $this->command->info("Iniciando inserción de carreras...\n");

        foreach ($carrerasData as $carreraData) {
            $nombreDepartamento = $carreraData['departamento'];
            $nombreCarrera = $carreraData['carrera'];

            // Buscar el departamento por nombre (case-insensitive)
            $departamento = DB::table('departamentos')
                ->whereRaw('LOWER(nombre) = ?', [strtolower($nombreDepartamento)])
                ->first();

            if (!$departamento) {
                // Registrar departamento no encontrado
                if (!in_array($nombreDepartamento, $departamentosNoEncontrados)) {
                    $departamentosNoEncontrados[] = $nombreDepartamento;
                }
                $omitidas++;
                continue;
            }

            // Verificar si la carrera ya existe para evitar duplicados
            $existe = DB::table('carreras')
                ->where('nombre', $nombreCarrera)
                ->where('departamento_id', $departamento->id)
                ->exists();

            if ($existe) {
                $this->command->warn("La carrera '{$nombreCarrera}' ya existe en el departamento '{$nombreDepartamento}'");
                $omitidas++;
                continue;
            }

            // Insertar la carrera
            DB::table('carreras')->insert([
                'nombre' => $nombreCarrera,
                'departamento_id' => $departamento->id,
                'estado' => 'activa',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $insertadas++;
            $this->command->info("✓ {$nombreCarrera} ({$nombreDepartamento})");
        }

        // Mostrar resumen
        $this->command->info("\n" . str_repeat('=', 60));
        $this->command->info("RESUMEN DE INSERCIÓN");
        $this->command->info(str_repeat('=', 60));
        $this->command->info("Carreras insertadas: {$insertadas}");
        $this->command->info("Carreras omitidas: {$omitidas}");

        if (!empty($departamentosNoEncontrados)) {
            $this->command->warn("\nDepartamentos no encontrados:");
            foreach ($departamentosNoEncontrados as $dept) {
                $this->command->warn("   - {$dept}");
            }
        }

        $this->command->info(str_repeat('=', 60) . "\n");
    }
}
