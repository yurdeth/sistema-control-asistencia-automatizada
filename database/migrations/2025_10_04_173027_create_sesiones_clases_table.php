<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('sesiones_clases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('horario_id')
                ->constrained('horarios')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->date('fecha_clase');
            $table->timestamp('hora_inicio_real')->nullable();
            $table->timestamp('hora_fin_real')->nullable();
            $table->integer('duracion_minutos')->nullable();
            $table->enum('estado', ['programada', 'en_curso', 'finalizada', 'cancelada', 'sin_marcar_salida'])->default('programada');
            $table->integer('retraso_minutos')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('sesiones_clases');
    }
};
