<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AulasSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * LEGACY SEEDER - NO USAR
     * Este seeder descargaba imágenes de Firebase (solo se ejecutó una vez)
     * Usar: AulasTableSeeder, AulasFotosSeeder, AulasVideosSeeder
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando importación de aulas desde JSON...');

        $jsonPath = database_path('seeders/data/aulas_pre.json');

        if (!file_exists($jsonPath)) {
            $this->command->error("❌ Archivo no encontrado: {$jsonPath}");
            return;
        }

        $aulasData = json_decode(file_get_contents($jsonPath), true);

        if (!$aulasData) {
            $this->command->error('❌ Error al parsear el JSON');
            return;
        }

        $this->command->info("📦 Se encontraron " . count($aulasData) . " aulas para importar");

        $progressBar = $this->command->getOutput()->createProgressBar(count($aulasData));
        $progressBar->start();

        foreach ($aulasData as $index => $aulaData) {
            $aulaId = $index + 1;

            try {
                $coordenadas = array_map('trim', explode(',', $aulaData['zona_coordenadas'] ?? '0,0'));
                $latitud = $coordenadas[0] ?? null;
                $longitud = $coordenadas[1] ?? null;


                DB::table('aulas')->insert([
                    'id' => $aulaId,
                    'codigo' => Str::random(10),
                    'nombre' => $aulaData['numero'],
                    'capacidad_pupitres' => $aulaData['capacidad'],
                    'ubicacion' => $aulaData['indicaciones'] ?? 'Sin indicaciones',
                    'indicaciones' => $aulaData['indicaciones'] ?? null,
                    'latitud' => $latitud,
                    'longitud' => $longitud,
                    'qr_code' => Str::uuid()->toString(),
                    'estado' => 'disponible',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);


                if (isset($aulaData['fotos']) && is_array($aulaData['fotos'])) {
                    $this->procesarFotos($aulaId, $aulaData['fotos']);
                }

                if (isset($aulaData['videos']) && is_array($aulaData['videos'])) {
                    foreach ($aulaData['videos'] as $video) {
                        DB::table('aula_videos')->insert([
                            'aula_id' => $aulaId,
                            'url' => $video['video_url'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                $progressBar->advance();

            } catch (\Exception $e) {
                $this->command->error("\n❌ Error en aula ID {$aulaId}: " . $e->getMessage());
                continue;
            }
        }

        $progressBar->finish();
        $this->command->newLine();
        $this->command->info('✅ Importación completada exitosamente');

        /*$aulas = [
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

        }*/
    }

    /**
     * Procesa y descarga las fotos de un aula desde Firebase
     *
     * @param int $aulaId ID del aula
     * @param array $fotos Array de fotos del JSON
     * @return void
     */
    private function procesarFotos(int $aulaId, array $fotos): void
    {
        foreach ($fotos as $foto) {
            try {
                // Obtener URL de la foto
                $fotoUrl = $foto['foto_url'] ?? null;

                if (!$fotoUrl) {
                    continue; // Skipear si no hay URL
                }

                // Descargar la imagen con timeout de 30 segundos
                $response = Http::timeout(30)->get($fotoUrl);

                // Verificar que la descarga fue exitosa
                if (!$response->successful()) {
                    $this->command->warn("\n⚠️  Fallo al descargar foto para aula ID {$aulaId}: HTTP {$response->status()}");
                    continue;
                }

                // Obtener el contenido de la imagen
                $imagenContenido = $response->body();

                // Obtener extensión original de la URL
                $extension = $this->obtenerExtension($fotoUrl);

                // Generar nombre único con hash
                $nombreArchivo = Str::random(16) . '.' . $extension;

                $rutaRelativa = 'aulas/' . $nombreArchivo;
                Storage::disk('public')->put($rutaRelativa, $imagenContenido);

                DB::table('aula_fotos')->insert([
                    'aula_id' => $aulaId,
                    'ruta' => $rutaRelativa,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            } catch (\Exception $e) {
                // Si falla la descarga o guardado, skipear esta foto sin detener el proceso
                $this->command->warn("\n⚠️  Error procesando foto para aula ID {$aulaId}: " . $e->getMessage());
                continue;
            }
        }
    }

    /**
     * Obtiene la extensión del archivo desde la URL
     *
     * @param string $url URL de la imagen
     * @return string Extensión del archivo (jpg, png, webp, etc.)
     */
    private function obtenerExtension(string $url): string
    {
        // Parsear la URL para obtener el path
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';

        // Obtener extensión del path
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        // Si no se encuentra extensión o es inválida, usar 'jpg' por defecto
        $extensionesValidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $extension = strtolower($extension);

        if (!in_array($extension, $extensionesValidas)) {
            return 'jpg'; // Extensión por defecto
        }

        return $extension;
    }

}
