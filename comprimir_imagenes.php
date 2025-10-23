#!/usr/bin/env php
<?php

$storageFolder = __DIR__ . '/storage/app/public/aulas';
$outputZip = __DIR__ . '/database/seeders/data/imagenes_aulas.zip';

if (!is_dir($storageFolder)) {
    echo "❌ No se encuentra la carpeta: {$storageFolder}\n";
    exit(1);
}

$files = glob($storageFolder . '/*');

if (empty($files)) {
    echo "❌ No hay archivos en {$storageFolder}\n";
    exit(1);
}

echo "📦 Comprimiendo " . count($files) . " imágenes...\n";

$zip = new ZipArchive();

if ($zip->open($outputZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    echo "❌ Error al crear el archivo ZIP\n";
    exit(1);
}

foreach ($files as $file) {
    if (is_file($file)) {
        $filename = basename($file);
        $zip->addFile($file, $filename);
    }
}

$zip->close();

$sizeMB = round(filesize($outputZip) / 1024 / 1024, 2);

echo "✅ Archivo creado: {$outputZip}\n";
echo "📊 Tamaño: {$sizeMB} MB\n";
echo "📁 Total de imágenes: " . count($files) . "\n";
