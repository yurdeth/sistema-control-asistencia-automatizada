<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('carreras', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->foreignId('departamento_id')
                ->constrained('departamentos')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->enum('estado', ['activa', 'inactiva'])->default('activa');
            $table->timestamps();

            // Índices y restricciones
            $table->unique(['nombre', 'departamento_id'], 'uq_carreras_nombre_departamento');
            $table->index('departamento_id', 'idx_carreras_departamento_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('carreras');
    }
};