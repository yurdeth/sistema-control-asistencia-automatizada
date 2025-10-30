<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\departamentos;
use App\Models\carreras;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_completo' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'telefono' => '+503 ' . fake()->numberBetween(2000, 9999) . '-' . fake()->numberBetween(1000, 9999),
            'password' => static::$password ??= Hash::make(config('factory.password')),
            'departamento_id' => null,
            'carrera_id' => null,
            'email_verificado' => fake()->boolean(80), // 80% verificados
            'estado' => fake()->randomElement(['activo', 'inactivo', 'suspendido']),
            'ultimo_acceso' => fake()->optional(0.7)->dateTimeBetween('-1 month', 'now'),
            'remember_token' => Str::random(10),
        ];
    }

    public function conDepartamento($departamentoId = null): static
    {
        return $this->state(function (array $attributes) use ($departamentoId) {
            if (!departamentos::exists()) {
                throw new \RuntimeException('No hay departamentos en la base de datos. Ejecuta DepartamentosSeeder primero.');
            }

            return [
                'departamento_id' => $departamentoId ?? departamentos::where('estado', 'activo')->inRandomOrder()->first()?->id,
                'carrera_id' => null,
            ];
        });
    }

    public function conCarrera($carreraId = null): static
    {
        return $this->state(function (array $attributes) use ($carreraId) {
            if (!carreras::exists()) {
                throw new \RuntimeException('No hay carreras en la base de datos. Ejecuta CarrerasSeeder primero.');
            }

            return [
                'carrera_id' => $carreraId ?? carreras::inRandomOrder()->first()?->id,
                'departamento_id' => null,
            ];
        });
    }

    public function verificado(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verificado' => true,
        ]);
    }

    public function noVerificado(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verificado' => false,
        ]);
    }

    public function activo(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'activo',
        ]);
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'inactivo',
        ]);
    }

    public function suspendido(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'suspendido',
        ]);
    }

    public function sinAsignacion(): static
    {
        return $this->state(fn (array $attributes) => [
            'departamento_id' => null,
            'carrera_id' => null,
        ]);
    }
}
