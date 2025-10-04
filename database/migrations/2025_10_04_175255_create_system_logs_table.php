<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('nivel', ['DEGUB', 'INFO', 'WARNING', 'ERROR', 'CRITICAL']);
            $table->string('modulo', 100);
            $table->string('accion', 255);
            $table->foreignId('usuario_id')
                ->constrained('usuarios')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->json('contexto')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('system_logs');
    }
};
