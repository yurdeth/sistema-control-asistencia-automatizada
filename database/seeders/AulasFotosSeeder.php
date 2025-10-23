<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class AulasFotosSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/aulas_fotos.json');
        $compressedFile = database_path('seeders/data/imagenes_aulas.7z');
        $storagePath = storage_path('app/public/aulas');

        if (!file_exists($jsonPath)) {
            $this->command->error("❌ aulas_fotos.json no encontrado");
            return;
        }

        $fotos = json_decode(file_get_contents($jsonPath), true);

        if (!$fotos || count($fotos) === 0) {
            $this->command->error('❌ Error al leer aulas_fotos.json');
            return;
        }

        $muestra = array_rand($fotos, min(5, count($fotos)));
        $muestra = is_array($muestra) ? $muestra : [$muestra];

        $todasExisten = true;
        foreach ($muestra as $idx) {
            $ruta = str_replace('aulas/', '', $fotos[$idx]['ruta']);
            if (!Storage::disk('public')->exists('aulas/' . $ruta)) {
                $todasExisten = false;
                break;
            }
        }

        if (!$todasExisten) {
            if (!file_exists($compressedFile)) {
                $this->command->error("❌ imagenes_aulas.7z no encontrado");
                return;
            }

            $this->command->info('📦 Descomprimiendo imágenes...');

            $tempDir = storage_path('app/temp_imagenes');
            if (!File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            exec("7z x " . escapeshellarg($compressedFile) . " -o" . escapeshellarg($tempDir) . " -y", $output, $returnCode);

            if ($returnCode !== 0) {
                $this->command->error('❌ Error al descomprimir el archivo');
                return;
            }

            if (!File::exists($storagePath)) {
                File::makeDirectory($storagePath, 0755, true);
            }

            $archivos = File::files($tempDir);
            foreach ($archivos as $archivo) {
                $nombreArchivo = $archivo->getFilename();
                $destino = $storagePath . '/' . $nombreArchivo;

                if (!file_exists($destino)) {
                    File::copy($archivo->getPathname(), $destino);
                }
            }

            File::deleteDirectory($tempDir);
            $this->command->info('✅ Imágenes copiadas al storage');
        } else {
            $this->command->info('✅ Imágenes ya existen en storage');
        }

        $this->command->info("📦 Insertando " . count($fotos) . " registros de fotos...");

        foreach ($fotos as $foto) {
            DB::table('aula_fotos')->insert([
                'id' => $foto['id'],
                'aula_id' => $foto['aula_id'],
                'ruta' => $foto['ruta'],
                'created_at' => $foto['created_at'],
                'updated_at' => $foto['updated_at'],
            ]);
        }

        $this->command->info('✅ Fotos insertadas');
    }
}
