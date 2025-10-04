<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder {
    /**
     * Run the database seeds.
     */

    // Creación del superusuario

    public function run(): void {
        DB::table('usuarios')->insert([
            'nombre_completo' => 'root',
            'email' => 'admin@admin.com',
            'telefono' => '+503 0000-0000',
            'password_hash' => Hash::make(env('ADMIN_PASSWORD')),
            'departamento_id' => 1,
            'email_verificado' => true,
            'token_verificacion' => null,
            'estado' => 'activo',
            'ultimo_acceso' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
