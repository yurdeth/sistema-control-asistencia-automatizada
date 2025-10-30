<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use ZipArchive;

class AulasFotosSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/aulas_fotos.json');
        $zipFile = database_path('seeders/data/imagenes_aulas.zip');
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
            if (!file_exists($zipFile)) {
                $this->command->error("❌ imagenes_aulas.zip no encontrado");
                return;
            }

            $this->command->info('📦 Descomprimiendo imágenes...');

            $zip = new ZipArchive();

            if ($zip->open($zipFile) !== true) {
                $this->command->error('❌ Error al abrir el archivo ZIP');
                return;
            }

            if (!File::exists($storagePath)) {
                File::makeDirectory($storagePath, 0755, true);
            }

            $zip->extractTo($storagePath);
            $zip->close();

            $this->command->info('✅ Imágenes extraídas al storage');
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
