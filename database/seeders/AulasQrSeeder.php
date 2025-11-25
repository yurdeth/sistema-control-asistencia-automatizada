<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use ZipArchive;

class AulasQrSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/aulas_qr.json');
        $zipFile = database_path('seeders/data/qr_aulas.zip');
        $storagePath = storage_path('app/public/aulas/qr');

        if (!file_exists($jsonPath)) {
            $this->command->error("aulas_qr.json no encontrado");
            return;
        }

        $qrs = json_decode(file_get_contents($jsonPath), true);

        if (!$qrs || count($qrs) === 0) {
            $this->command->error('Error al leer aulas_qr.json');
            return;
        }

        // Verificar si existen las imágenes QR tomando una muestra aleatoria de 5
        $muestra = array_rand($qrs, min(5, count($qrs)));
        $muestra = is_array($muestra) ? $muestra : [$muestra];

        $todasExisten = true;
        foreach ($muestra as $idx) {
            $ruta = $qrs[$idx]['ruta']; // La ruta ya incluye 'aulas/qr/nombre.png'
            $this->command->info('Verificando existencia de ' . $ruta);
            if (!Storage::disk('public')->exists($ruta)) {
                $todasExisten = false;
                break;
            }
        }

        if (!$todasExisten) {
            if (!file_exists($zipFile)) {
                $this->command->error("qr_aulas.zip no encontrado");
                return;
            }

            $this->command->info('Descomprimiendo imágenes QR...');

            $zip = new ZipArchive();

            if ($zip->open($zipFile) !== true) {
                $this->command->error('Error al abrir el archivo ZIP');
                return;
            }

            // Asegurar que el directorio exista
            if (!File::exists($storagePath)) {
                File::makeDirectory($storagePath, 0755, true);
            }

            $zip->extractTo($storagePath); // Extrae a storage/app/public/aulas/qr/
            $zip->close();

            $this->command->info('Imágenes QR extraídas al storage');
        } else {
            $this->command->info('Imágenes QR ya existen en storage');
        }

        // Verificar si los registros ya existen en la base de datos
        $registrosExistentes = DB::table('aula_qrs')->count();
        $totalRegistrosJson = count($qrs);

        if ($registrosExistentes >= $totalRegistrosJson) {
            $this->command->info("Los registros de QRs ya existen en la base de datos ({$registrosExistentes} registros)");
            $this->command->info('No se requiere insertar nada más');
            return;
        }

        $this->command->info("Insertando " . ($totalRegistrosJson - $registrosExistentes) . " registros faltantes...");

        $insertados = 0;
        $omitidos = 0;

        foreach ($qrs as $qr) {
            // Verificar si el registro ya existe por ID
            $existe = DB::table('aula_qrs')->where('id', $qr['id'])->exists();

            if ($existe) {
                $omitidos++;
                continue;
            }

            DB::table('aula_qrs')->insert([
                'id' => $qr['id'],
                'aula_id' => $qr['aula_id'],
                'ruta' => $qr['ruta'],
                'created_at' => $qr['created_at'],
                'updated_at' => $qr['updated_at'],
            ]);

            $insertados++;
        }

        $this->command->info("Proceso completado:");
        $this->command->info("   • Registros insertados: {$insertados}");
        $this->command->info("   • Registros omitidos (ya existían): {$omitidos}");
        $this->command->info("   • Total registros en BD: " . ($insertados + $omitidos));
    }
}
