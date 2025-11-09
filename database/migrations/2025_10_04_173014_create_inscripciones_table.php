<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('grupo_id')
                ->constrained('grupos')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->enum('estado', ['activo', 'retirado', 'finalizado'])->default('activo');
            $table->timestamp('fecha_retiro')->nullable();
            $table->text('motivo_retiro')->nullable();
            $table->decimal('nota_final', 5, 2)->nullable();
            $table->timestamps();
            $table->unique(['estudiante_id', 'grupo_id'], 'uq_inscripciones_estudiante_grupo');
            $table->index(['grupo_id', 'estado'], 'idx_inscripciones_grupo_estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('inscripciones');
    }
};
