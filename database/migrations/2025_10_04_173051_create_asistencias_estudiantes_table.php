<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('asistencias_estudiantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_clase_id')
                ->constrained('sesiones_clases')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('estudiante_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->time('hora_registro');
            $table->enum('estado', ['presente', 'tarde', 'ausente'])->default('presente');
            $table->boolean('validado_por_qr')->default(true);
            $table->unique(['sesion_clase_id', 'estudiante_id'], 'uq_asistencias_sesion_estudiante');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('asistencias_estudiantes');
    }
};
