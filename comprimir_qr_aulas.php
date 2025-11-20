#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$qrStorageFolder = __DIR__ . '/storage/app/public/aulas/qr';
$outputZip = __DIR__ . '/database/seeders/data/qr_aulas.zip';

if (!is_dir($qrStorageFolder)) {
    echo "No se encuentra la carpeta: {$qrStorageFolder}\n";
    echo "Asegúrate de haber ejecutado primero: php artisan db:seed --class=GeneradorAulasQrSeeder\n";
    exit(1);
}

$qrFiles = glob($qrStorageFolder . '/*');

if (empty($qrFiles)) {
    echo "No hay archivos QR en {$qrStorageFolder}\n";
    echo "Asegúrate de haber ejecutado primero: php artisan db:seed --class=GeneradorAulasQrSeeder\n";
    exit(1);
}

echo "Comprimiendo " . count($qrFiles) . " códigos QR...\n";

$zip = new ZipArchive();

if ($zip->open($outputZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    echo "Error al crear el archivo ZIP\n";
    exit(1);
}

$addedFiles = 0;
foreach ($qrFiles as $file) {
    if (is_file($file)) {
        $filename = basename($file);

        // Solo agregar archivos PNG
        if (pathinfo($filename, PATHINFO_EXTENSION) === 'png') {
            $zip->addFile($file, $filename);
            $addedFiles++;
            echo "   Agregado: {$filename}\n";
        }
    }
}

$zip->close();

if (!file_exists($outputZip)) {
    echo "Error: No se pudo crear el archivo ZIP\n";
    exit(1);
}

$sizeMB = round(filesize($outputZip) / 1024 / 1024, 2);

echo "\nArchivo ZIP creado: {$outputZip}\n";
echo "Tamaño: {$sizeMB} MB\n";
echo "Total de QRs comprimidos: {$addedFiles}\n";

echo "\nPróximos pasos:\n";
echo "   1. Exporta la tabla 'aula_qrs' desde tu base de datos a JSON:\n";
echo "      → Guarda como: database/seeders/data/aulas_qr.json\n";
echo "   2. Verifica que ambos archivos existan:\n";
echo "      → database/seeders/data/qr_aulas.zip\n";
echo "      → database/seeders/data/aulas_qr.json\n";
echo "   3. Listo para producción: El sistema usará AulasQrSeeder automáticamente\n";

// Verificar si el directorio data existe
$dataDir = __DIR__ . '/database/seeders/data';
if (!is_dir($dataDir)) {
    echo "\nCreando directorio database/seeders/data...\n";
    mkdir($dataDir, 0755, true);
    echo "Directorio creado\n";
}
