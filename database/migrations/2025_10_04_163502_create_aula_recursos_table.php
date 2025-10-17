<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('aula_recursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aula_id')
                ->constrained('aulas')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('recurso_tipo_id')
                ->constrained('recursos_tipos')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->integer('cantidad');
            $table->enum('estado', ['nuevo', 'bueno', 'regular', 'malo', 'mantenimiento'])->default('nuevo');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('aula_recursos');
    }
};
