<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('escaneos_qrs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aula_id')
                ->constrained('aulas')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('usuario_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('sesion_clase_id')
                ->constrained('sesiones_clases')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->enum('tipo_escaneo', ['entrada_docente', 'salida_docente', 'asistencia_estudiante']);
            $table->enum('resultado', ['exito', 'fallo', 'no_autorizado']);
            $table->string('motivo_fallo')->nullable();
            $table->ipAddress();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('escaneos_qrs');
    }
};
