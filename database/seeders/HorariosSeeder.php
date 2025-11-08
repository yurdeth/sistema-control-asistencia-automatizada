<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\grupos;
use App\Models\aulas;
use App\Models\horarios;
use Carbon\Carbon;

class HorariosSeeder extends Seeder
{
    private array $bloquesHorarios = [
        ['inicio' => '07:00:00', 'fin' => '09:00:00', 'horas' => 2],
        ['inicio' => '09:00:00', 'fin' => '12:00:00', 'horas' => 3],
        ['inicio' => '07:00:00', 'fin' => '10:00:00', 'horas' => 3],
        ['inicio' => '10:00:00', 'fin' => '12:00:00', 'horas' => 2],

        ['inicio' => '13:00:00', 'fin' => '15:00:00', 'horas' => 2],
        ['inicio' => '15:00:00', 'fin' => '18:00:00', 'horas' => 3],
        ['inicio' => '13:00:00', 'fin' => '16:00:00', 'horas' => 3],
        ['inicio' => '16:00:00', 'fin' => '18:00:00', 'horas' => 2],
    ];

    private array $diasSemana = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];

    // Cache de ocupación para validaciones rápidas
    private array $ocupacionAulas = [];
    private array $ocupacionDocentes = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!grupos::exists()) {
            $this->command->error('No hay grupos. Ejecuta GruposSeeder primero.');
            return;
        }

        if (!aulas::exists()) {
            $this->command->error('No hay aulas. Ejecuta AulasSeeder primero.');
            return;
        }

        $this->command->info('Iniciando creación de horarios...');

        $grupos = grupos::with(['docente', 'materia'])->where('estado', 'activo')->get();
        $aulas = aulas::where('estado', 'disponible')->pluck('id')->toArray();

        if (empty($aulas)) {
            $this->command->error('No hay aulas disponibles.');
            return;
        }

        $horariosCreados = 0;
        $gruposSinHorario = 0;

        $progressBar = $this->command->getOutput()->createProgressBar($grupos->count());
        $progressBar->start();

        foreach ($grupos as $grupo) {
            $sesionesCreadas = $this->crearHorarioParaGrupo($grupo, $aulas);

            if ($sesionesCreadas === 2) {
                $horariosCreados += 2;
            } else {
                $gruposSinHorario++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->command->newLine();
        $this->command->info("✅ {$horariosCreados} horarios creados exitosamente");

        if ($gruposSinHorario > 0) {
            $this->command->warn("{$gruposSinHorario} grupos no pudieron asignarse por conflictos de horarios");
        }
    }

    /**
     *
     * @param grupos $grupo
     * @param array $aulasDisponibles
     * @return int Número de sesiones creadas (0 o 2)
     */
    private function crearHorarioParaGrupo(grupos $grupo, array $aulasDisponibles): int
    {
        $intentosMaximos = 50;
        $intentos = 0;

        while ($intentos < $intentosMaximos) {
            // Seleccionar combinación de bloques que sumen 5 horas
            $combinacion = $this->obtenerCombinacion5Horas();

            if (!$combinacion) {
                return 0;
            }

            // Seleccionar 2 días diferentes de la semana
            $diasSeleccionados = fake()->randomElements($this->diasSemana, 2);

            // Seleccionar aula
            $aulaId = fake()->randomElement($aulasDisponibles);

            $sesion1 = [
                'grupo_id' => $grupo->id,
                'aula_id' => $aulaId,
                'dia_semana' => $diasSeleccionados[0],
                'hora_inicio' => $combinacion[0]['inicio'],
                'hora_fin' => $combinacion[0]['fin'],
            ];

            $sesion2 = [
                'grupo_id' => $grupo->id,
                'aula_id' => $aulaId,
                'dia_semana' => $diasSeleccionados[1],
                'hora_inicio' => $combinacion[1]['inicio'],
                'hora_fin' => $combinacion[1]['fin'],
            ];

            // Validar conflictos
            if ($this->validarSesion($sesion1, $grupo->docente_id) &&
                $this->validarSesion($sesion2, $grupo->docente_id)) {

                // Crear ambas sesiones
                horarios::create($sesion1);
                horarios::create($sesion2);

                // Registrar ocupación
                $this->registrarOcupacion($sesion1, $grupo->docente_id);
                $this->registrarOcupacion($sesion2, $grupo->docente_id);

                return 2;
            }

            $intentos++;
        }

        return 0; // No se pudo asignar
    }

    /**
     * Obtiene una combinación de 2 bloques que sumen 5 horas
     *
     * @return array|null [bloque1, bloque2] o null
     */
    private function obtenerCombinacion5Horas(): ?array
    {
        // Combinaciones válidas: 2h+3h o 3h+2h
        $bloques2h = array_filter($this->bloquesHorarios, fn($b) => $b['horas'] === 2);
        $bloques3h = array_filter($this->bloquesHorarios, fn($b) => $b['horas'] === 3);

        if (empty($bloques2h) || empty($bloques3h)) {
            return null;
        }

        // Mezclar aleatoriamente: 2h primero o 3h primero
        if (fake()->boolean()) {
            return [
                fake()->randomElement($bloques2h),
                fake()->randomElement($bloques3h),
            ];
        } else {
            return [
                fake()->randomElement($bloques3h),
                fake()->randomElement($bloques2h),
            ];
        }
    }

    /**
     * Valida que no haya conflictos de aula ni de docente
     *
     * @param array $sesion
     * @param int $docenteId
     * @return bool
     */
    private function validarSesion(array $sesion, int $docenteId): bool
    {
        $key = "{$sesion['dia_semana']}|{$sesion['hora_inicio']}|{$sesion['hora_fin']}";

        // Validar conflicto de aula
        $aulaKey = "aula_{$sesion['aula_id']}_{$key}";
        if (isset($this->ocupacionAulas[$aulaKey])) {
            return false; // Aula ocupada
        }

        // Validar conflicto de docente
        $docenteKey = "docente_{$docenteId}_{$key}";
        if (isset($this->ocupacionDocentes[$docenteKey])) {
            return false; // Docente ocupado
        }

        return true;
    }

    /**
     * Registra la ocupación de aula y docente
     *
     * @param array $sesion
     * @param int $docenteId
     */
    private function registrarOcupacion(array $sesion, int $docenteId): void
    {
        $key = "{$sesion['dia_semana']}|{$sesion['hora_inicio']}|{$sesion['hora_fin']}";

        $this->ocupacionAulas["aula_{$sesion['aula_id']}_{$key}"] = true;
        $this->ocupacionDocentes["docente_{$docenteId}_{$key}"] = true;
    }
}
