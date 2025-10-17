<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('estadisticas_aulas_diarias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aula_id')
                ->constrained('aulas')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->date('fecha');
            $table->integer('minutos_ocupada')->default(0);
            $table->decimal('porcentaje_ocupacion', 5, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('estadisticas_aulas_diarias');
    }
};
