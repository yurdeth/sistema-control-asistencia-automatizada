<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_completo');
            $table->string('email')->unique();
            $table->string('telefono')->nullable();
            $table->string('password');
            $table->foreignId('departamento_id')
                ->nullable()
                ->constrained('departamentos')
                ->onDelete('no action')
                ->onUpdate('no action');
            $table->foreignId('carrera_id')
                ->nullable()
                ->constrained('carreras')
                ->onDelete('no action')
                ->onUpdate('no action');
            $table->boolean('email_verificado')->default(false);
            $table->enum('estado', ['activo', 'inactivo', 'suspendido'])->default('activo');
            $table->timestamp('ultimo_acceso')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Agregar el check constraint para la lógica de negocio
        DB::statement('ALTER TABLE users ADD CONSTRAINT chk_usuario_rol_asignacion CHECK (
            (carrera_id IS NOT NULL AND departamento_id IS NULL)
                OR
            (carrera_id IS NULL)
        )');

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        // Eliminar el check constraint antes de eliminar las tablas
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS chk_usuario_rol_asignacion');

        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
