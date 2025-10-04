<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('solicitudes_inscripcions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')
                ->constrained('usuarios')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('grupo_id')
                ->constrained('grupos')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->enum('tipo_solicitud', ['estudiante_solicita', 'docente_invita'])->default('estudiante_solicita');
            $table->enum('estado', ['pendiente', 'aceptada', 'rechazada', 'cancelada'])->default('pendiente');
            $table->foreignId('respondido_por')
                ->constrained('usuarios')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('solicitudes_inscripcions');
    }
};
