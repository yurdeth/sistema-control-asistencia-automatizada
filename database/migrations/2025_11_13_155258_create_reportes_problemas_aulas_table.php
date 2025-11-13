<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reportes_problemas_aulas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aula_id')
                ->constrained('aulas')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('usuario_reporta_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->enum('categoria', [
                'recurso_defectuoso',
                'qr_danado',
                'limpieza',
                'infraestructura',
                'conectividad',
                'otro'
            ]);
            $table->text('descripcion');
            $table->enum('estado', [
                'reportado',
                'en_revision',
                'asignado',
                'en_proceso',
                'resuelto',
                'rechazado',
                'cerrado'
            ])->default('reportado');
            $table->string('foto_evidencia', 500)->nullable();
            $table->foreignId('usuario_asignado_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->onUpdate('cascade');
            $table->timestamp('fecha_resolucion')->nullable();
            $table->text('notas_resolucion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reportes_problemas_aulas');
    }
};
