<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('carrera_id')->nullable()->after('departamento_id');
            
            // Si quieres agregar foreign key (opcional):
            // $table->foreign('carrera_id')->references('id')->on('carreras')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // $table->dropForeign(['carrera_id']); // Si agregaste foreign key
            $table->dropColumn('carrera_id');
        });
    }
};