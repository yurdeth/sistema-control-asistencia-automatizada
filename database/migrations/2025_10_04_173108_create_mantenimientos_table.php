<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aula_id')
                ->constrained('aulas')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('usuario_registro_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->text('motivo');
            $table->date('fecha_inicio');
            $table->date('fecha_fin_programada');
            $table->date('fecha_fin_real')->nullable();
            $table->string('realizado_por', 255)->nullable();
            $table->enum('estado', ['programado', 'en_proceso', 'finalizado', 'cancelado'])->default('programado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('mantenimientos');
    }
};
