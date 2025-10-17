<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AulaSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $aulas = [
            // Primer Nivel
            [
                'codigo' => 'AULA-31-1N',
                'nombre' => 'Aula 31 1er Nivel',
                'capacidad_pupitres' => 100,
                'ubicacion' => 'Edificio Minerva, 300 m al norte sobre la entrada principal, sobre la calle principal de la Universidad',
                'estado' => 'disponible',
            ],
            [
                'codigo' => 'AULA-32-1N',
                'nombre' => 'Aula 32 1er Nivel',
                'capacidad_pupitres' => 100,
                'ubicacion' => 'Edificio Minerva, 300 m al norte sobre la entrada principal, sobre la calle principal de la Universidad',
                'estado' => 'disponible',
            ],
            [
                'codigo' => 'AULA-33-1N',
                'nombre' => 'Aula 33 1er Nivel',
                'capacidad_pupitres' => 60,
                'ubicacion' => 'Edificio Minerva, 300 m al norte sobre la entrada principal, sobre la calle principal de la Universidad',
                'estado' => 'disponible',
            ],

            // Segundo Nivel
            [
                'codigo' => 'AULA-201-2N',
                'nombre' => 'Aula 201 2do Nivel',
                'capacidad_pupitres' => 50,
                'ubicacion' => 'Edificio Minerva, 300 m al norte sobre la entrada principal, sobre la calle principal de la Universidad',
                'estado' => 'disponible',
            ],
            [
                'codigo' => 'AULA-202-2N',
                'nombre' => 'Aula 202 2do Nivel',
                'capacidad_pupitres' => 50,
                'ubicacion' => 'Edificio Minerva, 300 m al norte sobre la entrada principal, sobre la calle principal de la Universidad',
                'estado' => 'disponible',
            ],
            [
                'codigo' => 'AULA-203-2N',
                'nombre' => 'Aula 203 2do Nivel',
                'capacidad_pupitres' => 50,
                'ubicacion' => 'Edificio Minerva, 300 m al norte sobre la entrada principal, sobre la calle principal de la Universidad',
                'estado' => 'disponible',
            ],
            [
                'codigo' => 'AULA-204-2N',
                'nombre' => 'Aula 204 2do Nivel',
                'capacidad_pupitres' => 50,
                'ubicacion' => 'Edificio Minerva, 300 m al norte sobre la entrada principal, sobre la calle principal de la Universidad',
                'estado' => 'disponible',
            ],
            [
                'codigo' => 'AULA-205-2N',
                'nombre' => 'Aula 205 2do Nivel',
                'capacidad_pupitres' => 50,
                'ubicacion' => 'Edificio Minerva, 300 m al norte sobre la entrada principal, sobre la calle principal de la Universidad',
                'estado' => 'disponible',
            ],
            [
                'codigo' => 'AULA-206-2N',
                'nombre' => 'Aula 206 2do Nivel',
                'capacidad_pupitres' => 50,
                'ubicacion' => 'Edificio Minerva, 300 m al norte sobre la entrada principal, sobre la calle principal de la Universidad',
                'estado' => 'disponible',
            ],

            // Tercer Nivel
            [
                'codigo' => 'AULA-207-3N',
                'nombre' => 'Aula 207 3er Nivel',
                'capacidad_pupitres' => 50,
                'ubicacion' => 'Edificio Minerva, 300 m al norte sobre la entrada principal, sobre la calle principal de la Universidad',
                'estado' => 'disponible',
            ],
            [
                'codigo' => 'AULA-208-3N',
                'nombre' => 'Aula 208 3er Nivel',
                'capacidad_pupitres' => 50,
                'ubicacion' => 'Edificio Minerva, 300 m al norte sobre la entrada principal, sobre la calle principal de la Universidad',
                'estado' => 'disponible',
            ],
            [
                'codigo' => 'AULA-209-3N',
                'nombre' => 'Aula 209 3er Nivel',
                'capacidad_pupitres' => 50,
                'ubicacion' => 'Edificio Minerva, 300 m al norte sobre la entrada principal, sobre la calle principal de la Universidad',
                'estado' => 'disponible',
            ],
            [
                'codigo' => 'AULA-210-3N',
                'nombre' => 'Aula 210 3er Nivel',
                'capacidad_pupitres' => 50,
                'ubicacion' => 'Edificio Minerva, 300 m al norte sobre la entrada principal, sobre la calle principal de la Universidad',
                'estado' => 'disponible',
            ],
            [
                'codigo' => 'AULA-211-3N',
                'nombre' => 'Aula 211 3er Nivel',
                'capacidad_pupitres' => 50,
                'ubicacion' => 'Edificio Minerva, 300 m al norte sobre la entrada principal, sobre la calle principal de la Universidad',
                'estado' => 'disponible',
            ],
            [
                'codigo' => 'AULA-212-3N',
                'nombre' => 'Aula 212 3er Nivel',
                'capacidad_pupitres' => 50,
                'ubicacion' => 'Edificio Minerva, 300 m al norte sobre la entrada principal, sobre la calle principal de la Universidad',
                'estado' => 'disponible',
            ],
        ];

   
        foreach ($aulas as $index => $aula) {
          
            $qrContent = url('/aulas/' . $aula['codigo']);
            
        
            $qrCodeSvg = QrCode::format('svg')
                              ->size(400)
                              ->errorCorrection('H')
                              ->generate($qrContent);

   
            DB::table('aulas')->insert([
                'codigo' => $aula['codigo'],
                'nombre' => $aula['nombre'],
                'capacidad_pupitres' => $aula['capacidad_pupitres'],
                'ubicacion' => $aula['ubicacion'],
                'qr_code' => $qrCodeSvg, 
                'estado' => $aula['estado'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $numero = $index + 1;
            
        }
    }
      
}