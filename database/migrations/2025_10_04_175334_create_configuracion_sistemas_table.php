<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('configuracion_sistemas', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 100)->unique();
            $table->text('valor');
            $table->enum('tipo_dato', ['string', 'integer', 'boolean'])->default('string');
            $table->text('descripcion')->nullable();
            $table->string('categoria', 50)->nullable();
            $table->boolean('modificable')->default(true);
            $table->foreignId('usuario_identificacion_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('configuracion_sistemas');
    }
};
