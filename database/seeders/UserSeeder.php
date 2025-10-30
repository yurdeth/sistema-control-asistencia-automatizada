<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\usuario_roles;
use App\Models\roles;
use App\Models\departamentos;
use App\Models\carreras;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (!roles::exists()) {
            $this->command->error('No hay roles en la base de datos. Ejecuta RolesSeeder primero.');
            return;
        }

        $hayDepartamentos = departamentos::exists();
        $hayCarreras = carreras::exists();

        if (!$hayDepartamentos) {
            $this->command->warn('No hay departamentos. Los usuarios no tendran departamento asignado.');
        }

        if (!$hayCarreras) {
            $this->command->warn('No hay carreras. Los usuarios no tendran carrera asignada.');
        }

        $this->command->info('Iniciando creacion de usuarios...');

        DB::table('users')->insert([
            'nombre_completo' => 'root',
            'email' => env('ADMIN_EMAIL'),
            'telefono' => '+503 0000-0000',
            'password' => Hash::make(env('ADMIN_PASSWORD')),
            'departamento_id' => 9,
            'email_verificado' => true,
            'estado' => 'activo',
            'ultimo_acceso' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('usuario_roles')->insert([
            'usuario_id' => 1,
            'rol_id' => 1,
            'asignado_por_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userRoot = User::find(1);

        $admins = collect();
        for ($i = 0; $i < 5; $i++) {
            $admin = User::factory()
                ->verificado()
                ->activo()
                ->sinAsignacion()
                ->create();

            usuario_roles::create([
                'usuario_id' => $admin->id,
                'rol_id' => 2,
                'asignado_por_id' => $userRoot->id,
            ]);

            $admins->push($admin);
        }
        $this->command->info('Administradores Academicos creados');

        if ($hayDepartamentos) {
            $this->command->info('Creando Jefes de Departamento...');
            $jefesDepartamento = collect();
            for ($i = 0; $i < 10; $i++) {
                $jefe = User::factory()
                    ->verificado()
                    ->activo()
                    ->conDepartamento()
                    ->create();

                usuario_roles::create([
                    'usuario_id' => $jefe->id,
                    'rol_id' => 3,
                    'asignado_por_id' => $admins->random()->id,
                ]);

                $jefesDepartamento->push($jefe);
            }
            $this->command->info('Jefes de Departamento creados');
        } else {
            $jefesDepartamento = collect([$userRoot]);
            $this->command->warn('Saltando Jefes de Departamento (no hay departamentos)');
        }

        if ($hayCarreras) {
            $this->command->info('Creando Coordinadores de Carrera...');
            $coordinadores = collect();
            for ($i = 0; $i < 15; $i++) {
                $coordinador = User::factory()
                    ->verificado()
                    ->activo()
                    ->conCarrera()
                    ->create();

                usuario_roles::create([
                    'usuario_id' => $coordinador->id,
                    'rol_id' => 4,
                    'asignado_por_id' => $jefesDepartamento->random()->id,
                ]);

                $coordinadores->push($coordinador);
            }
            $this->command->info('Coordinadores de Carrera creados');
        } else {
            $coordinadores = collect([$userRoot]);
            $this->command->warn('Saltando Coordinadores (no hay carreras)');
        }

        $this->command->info('Creando Docentes...');
        for ($i = 0; $i < 50; $i++) {
            $usarDepartamento = false;

            if ($hayDepartamentos && $hayCarreras) {
                $usarDepartamento = fake()->boolean();
            } elseif ($hayDepartamentos) {
                $usarDepartamento = true;
            }

            if ($usarDepartamento) {
                $docente = User::factory()->verificado()->activo()->conDepartamento()->create();
            } elseif ($hayCarreras) {
                $docente = User::factory()->verificado()->activo()->conCarrera()->create();
            } else {
                $docente = User::factory()->verificado()->activo()->sinAsignacion()->create();
            }

            usuario_roles::create([
                'usuario_id' => $docente->id,
                'rol_id' => 5,
                'asignado_por_id' => $coordinadores->random()->id,
            ]);
        }
        $this->command->info('50 Docentes creados');

        if ($hayCarreras) {
            $this->command->info('Creando Estudiantes...');
            for ($i = 0; $i < 200; $i++) {
                $estudiante = User::factory()
                    ->conCarrera()
                    ->create([
                        'email_verificado' => fake()->boolean(85),
                        'estado' => fake()->randomElement(['activo', 'activo', 'activo', 'inactivo']),
                    ]);

                usuario_roles::create([
                    'usuario_id' => $estudiante->id,
                    'rol_id' => 6,
                    'asignado_por_id' => $coordinadores->random()->id,
                ]);
            }
            $this->command->info('200 Estudiantes creados');
        } else {
            $this->command->warn('Saltando Estudiantes (no hay carreras)');
        }

        // Resumen
        $this->command->newLine();
        $this->command->info('Usuarios creados exitosamente:');
    }
}
