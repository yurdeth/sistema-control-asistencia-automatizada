<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\grupos>
 */
class GruposFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = \App\Models\grupos::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero_grupo' => fake()->numberBetween(1, 999),
            'capacidad_maxima' => fake()->randomElement([30, 40, 50, 60, 80, 100]),
            'estudiantes_inscrito' => 0, //Valor dinámico con las funcionalidades de inscripción
            'estado' => 'activo',
        ];
    }
}
