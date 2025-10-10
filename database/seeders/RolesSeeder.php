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
                'nombre' => 'root',
                'descripcion' => 'Super usuario con todos los permisos del sistema',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'administrador_academico',
                'descripcion' => 'Administrador académico con permisos para gestionar usuarios y configuraciones académicas',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'gestor_departamentos',
                'descripcion' => 'Gestor de departamentos con permisos para administrar departamentos y asignar coordinadores',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'coordinador_carreras',
                'descripcion' => 'Coordinador de carreras con permisos para gestionar planes de estudio y asignar docentes',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'docente',
                'descripcion' => 'Docente con permisos para gestionar sus clases y calificaciones',
                'created_at' => now(),
                'updated_at' => now()],
            [
                'nombre' => 'estudiante',
                'descripcion' => 'Estudiante con permisos para inscribirse en cursos y ver sus calificaciones',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'invitado',
                'descripcion' => 'Usuario invitado con permisos limitados para explorar el sistema',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }
}
