<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder {
    /**
     * Run the database seeds.
     */

    /*
     * root
     * administrador académico
     * gestor de departamentos
     * coordinador de carreras
     * docentes
     * estudiante
     * invitado
     * */

    public function run(): void {
        DB::table('roles')->insert([
            [
                'id' => 1,
                'nombre' => 'root',
                'descripcion' => 'Super usuario con la mayoría de permisos del sistema',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 2,
                'nombre' => 'administrador_academico',
                'descripcion' => 'Administrador académico con permisos para gestionar usuarios y configuraciones académicas',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 3,
                'nombre' => 'jefe_departamento',
                'descripcion' => 'Gestor de departamentos con permisos para administrar departamentos y asignar coordinadores',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 4,
                'nombre' => 'coordinador_carreras',
                'descripcion' => 'Coordinador de carreras con permisos para gestionar planes de estudio y asignar docentes',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 5,
                'nombre' => 'docente',
                'descripcion' => 'Docente con permisos para gestionar sus clases y calificaciones',
                'created_at' => now(),
                'updated_at' => now()],
            [
                'id' => 6,
                'nombre' => 'estudiante',
                'descripcion' => 'Estudiante con permisos para inscribirse en cursos y ver sus calificaciones',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 7,
                'nombre' => 'invitado',
                'descripcion' => 'Usuario invitado con permisos limitados para explorar el sistema',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }
}
