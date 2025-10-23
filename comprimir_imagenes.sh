#!/bin/bash

STORAGE_PATH="storage/app/public/aulas"
OUTPUT_FILE="database/seeders/data/imagenes_aulas.7z"

if [ ! -d "$STORAGE_PATH" ]; then
    echo "❌ No se encuentra la carpeta $STORAGE_PATH"
    exit 1
fi

echo "Comprimiendo imágenes desde $STORAGE_PATH..."

# Guardar directorio actual
CURRENT_DIR=$(pwd)

# Ir a la carpeta de origen
cd "$STORAGE_PATH" || exit 1

# Comprimir todo el contenido usando ruta absoluta para el output
7z a -t7z -mx=9 "$CURRENT_DIR/$OUTPUT_FILE" ./*

# Volver al directorio original
cd "$CURRENT_DIR"

if [ $? -eq 0 ]; then
    echo "✅ Archivo creado: $OUTPUT_FILE"
    echo "📊 Tamaño del archivo:"
    du -h "$OUTPUT_FILE"
else
    echo "❌ Error al comprimir"
    exit 1
fi
