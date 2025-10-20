<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Verifica si las columnas existen antes de intentar modificarlas
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'departamento_id')) {
                $table->unsignedBigInteger('departamento_id')->nullable()->change();
            }

            if (Schema::hasColumn('users', 'carrera_id')) {
                $table->unsignedBigInteger('carrera_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'departamento_id')) {
                $table->unsignedBigInteger('departamento_id')->nullable(false)->change();
            }

            if (Schema::hasColumn('users', 'carrera_id')) {
                $table->unsignedBigInteger('carrera_id')->nullable(false)->change();
            }
        });
    }
};

