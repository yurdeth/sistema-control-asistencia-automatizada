<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('historial_aulas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aula_id')
                ->constrained('aulas')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('usuario_modificacion_id')
                ->constrained('usuarios')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->string('campo_modificado', 100);
            $table->text('valor_anterior');
            $table->text('valor_nuevo');
            $table->enum('tipo_operacion', ['creacion', 'actualizacion', 'cambio_estado']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('historial_aulas');
    }
};
