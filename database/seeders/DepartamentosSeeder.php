<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartamentosSeeder extends Seeder {
    /**
     * Run the database seeds.
     */

    //Ingenieria y arquitectura
    //Medicina
    //Economía
    //Ciencias y humanidades
    //Jurisprudencia y ciencias sociales
    //Agronomía
    //Departamento de ciencias naturales y matemática
    //Quimica y farmacia

    public function run(): void {
        DB::table("departamentos")->insert([
            [
                'nombre' => 'Ingeniería y Arquitectura',
                'descripcion' => 'Departamento encargado de las carreras de ingeniería civil, arquitectura, ingeniería eléctrica, ingeniería mecánica, entre otras.',
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Medicina',
                'descripcion' => 'Departamento que agrupa las carreras relacionadas con la salud humana, incluyendo medicina, enfermería, odontología y otras especialidades médicas.',
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Economía',
                'descripcion' => 'Departamento dedicado a las ciencias económicas y administrativas, ofreciendo programas en economía, administración de empresas, contabilidad y finanzas.',
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Ciencias y Humanidades',
                'descripcion' => 'Departamento que abarca una amplia gama de disciplinas, incluyendo historia, filosofía, sociología, literatura y artes.',
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Jurisprudencia y Ciencias Sociales',
                'descripcion' => 'Departamento enfocado en el estudio del derecho y las ciencias sociales, ofreciendo programas en derecho, ciencias políticas y relaciones internacionales.',
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Agronomía',
                'descripcion' => 'Departamento especializado en ciencias agrícolas y ambientales, con programas en agronomía, ingeniería agrícola y gestión ambiental.',
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Ciencias Naturales y Matemática',
                'descripcion' => 'Departamento que cubre disciplinas científicas fundamentales como biología, química, física y matemáticas.',
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Química y Farmacia',
                'descripcion' => 'Departamento dedicado al estudio de la química aplicada y la farmacia, ofreciendo programas en química farmacéutica y tecnología farmacéutica.',
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
