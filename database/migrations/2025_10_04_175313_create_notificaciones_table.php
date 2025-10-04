<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_notificacion_id')
                ->constrained('tipos_notificacion')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('usuario_destino_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->string('titulo');
            $table->text('mensaje');
            $table->enum('canal', ['email', 'push']);
            $table->enum('estado', ['pendiente', 'enviada', 'fallida', 'leida'])->default('pendiente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('notificaciones');
    }
};
