<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\materias;
use App\Models\ciclos_academicos;
use App\Models\User;
use App\Models\grupos;

class GruposSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Validar que existen las tablas relacionadas
        if (!materias::exists()) {
            $this->command->error('No hay materias. Ejecuta MateriasSeeder primero.');
            return;
        }

        if (!ciclos_academicos::exists()) {
            $this->command->error('No hay ciclos académicos. Ejecuta CiclosAcademicosSeeder primero.');
            return;
        }

        // Obtener docentes
        $docentes = DB::table('usuario_roles')
            ->where('rol_id', 5)
            ->pluck('usuario_id')
            ->toArray();

        if (empty($docentes)) {
            $this->command->error('No hay docentes (rol_id = 5). Ejecuta UserSeeder primero.');
            return;
        }

        $this->command->info('Iniciando creación de grupos...');

        // Obtener todas las materias activas
        $materias = materias::where('estado', 'activa')->get();

        // Obtener ciclo activo o el más reciente
        $cicloActivo = ciclos_academicos::where('estado', 'activo')
            ->orWhere('estado', 'planificado')
            ->orderBy('fecha_inicio', 'desc')
            ->first();

        if (!$cicloActivo) {
            $this->command->error('No hay ciclos activos o planificados.');
            return;
        }

        $gruposCreados = 0;
        $targetGrupos = 400;

        $progressBar = $this->command->getOutput()->createProgressBar($targetGrupos);
        $progressBar->start();

        // Crear grupos distribuyendo entre las materias
        while ($gruposCreados < $targetGrupos && $materias->count() > 0) {

            $materia = $materias->random();

            $numGruposPorMateria = fake()->numberBetween(1, 3);

            for ($i = 0; $i < $numGruposPorMateria && $gruposCreados < $targetGrupos; $i++) {
                $docenteId = fake()->randomElement($docentes);

                $capacidadMaxima = fake()->randomElement([30, 40, 50, 60, 80, 100]);

                grupos::factory()->create([
                    'materia_id' => $materia->id,
                    'ciclo_id' => $cicloActivo->id,
                    'docente_id' => $docenteId,
                    'numero_grupo' => fake()->unique()->numberBetween(1, 999),
                    'capacidad_maxima' => $capacidadMaxima,
                    'estudiantes_inscrito' => 0,
                    'estado' => 'activo',
                ]);

                $gruposCreados++;
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->command->newLine();
        $this->command->info("✅ {$gruposCreados} grupos creados exitosamente para el ciclo: {$cicloActivo->nombre}");
    }
}
