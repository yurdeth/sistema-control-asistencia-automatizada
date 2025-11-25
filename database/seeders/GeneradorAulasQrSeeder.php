<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\aulas;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GeneradorAulasQrSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * ESTE SEEDER SOLO DEBE EJECUTARSE UNA VEZ PARA GENERAR LOS QRs INICIALES
     */
    public function run(): void
    {
        $this->command->info('Iniciando generación de Códigos QR para todas las aulas...');

        // Asegurar que el directorio exista
        $qrDirectory = 'aulas/qr';
        if (!Storage::disk('public')->exists($qrDirectory)) {
            Storage::disk('public')->makeDirectory($qrDirectory);
            $this->command->info("Directorio creado: storage/app/public/{$qrDirectory}");
        }

        // Obtener todas las aulas de la base de datos
        $aulas = aulas::all();

        if ($aulas->isEmpty()) {
            $this->command->error('No se encontraron aulas en la base de datos. Ejecuta primero AulasTableSeeder.');
            return;
        }

        $this->command->info("Procesando {$aulas->count()} aulas...");

        $generados = 0;
        $omitidos = 0;

        foreach ($aulas as $aula) {
            try {
                // Verificar si ya existe un QR para esta aula
                $existingQr = DB::table('aula_qrs')->where('aula_id', $aula->id)->first();

                if ($existingQr) {
                    $this->command->line("Aula {$aula->codigo} ya tiene QR. Omitiendo...");
                    $omitidos++;
                    continue;
                }

                // Generar nombre de archivo basado en el código del aula
                $fileName = \Str::random() . '.png';
                $filePath = $qrDirectory . '/' . $fileName;
                $fullPath = storage_path('app/public/' . $filePath);

                // Generar el código QR optimizado para mejor escaneo
                QrCode::format('png')
                    ->size(300)
                    ->errorCorrection('L')  // Nivel bajo = QR más simple
                    ->margin(1)             // Margen mínimo
                    ->encoding('UTF-8')     // Codificación explícita
                    ->generate($aula->qr_code, $fullPath);

                // Insertar registro en la tabla aula_qrs
                $qrId = DB::table('aula_qrs')->insertGetId([
                    'aula_id' => $aula->id,
                    'ruta' => $filePath,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->command->info("QR generado para aula {$aula->codigo}: {$fileName} (ID: {$qrId})");
                $generados++;

            } catch (\Exception $e) {
                $this->command->error("Error generando QR para aula {$aula->codigo}: " . $e->getMessage());
            }
        }

        $this->command->info("Proceso completado:");
        $this->command->info("   • QRs generados: {$generados}");
        $this->command->info("   • QRs omitidos (ya existían): {$omitidos}");
        $this->command->info("   • Total aulas procesadas: {$aulas->count()}");

        if ($generados > 0) {
            $this->command->warn("\n  IMPORTANTE: Ahora puedes exportar la tabla 'aula_qrs' a JSON y comprimir los archivos QR.");
            $this->command->warn("   1. Exporta 'aula_qrs' a: database/seeders/data/aulas_qr.json");
            $this->command->warn("   2. Comprime storage/app/public/aulas/qr/ a: database/seeders/data/qr_aulas.zip");
        }
    }
}
