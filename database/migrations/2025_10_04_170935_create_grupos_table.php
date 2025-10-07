<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materia_id')
                ->constrained('materias')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('ciclo_id')
                ->constrained('ciclos_academicos')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('docente_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->integer('numero_grupo');
            $table->integer('capacidad_maxima');
            $table->integer('estudiantes_inscrito')->default(0);
            $table->enum('estado', ['activo', 'finalizado', 'cancelado'])->default('activo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('grupos');
    }
};
