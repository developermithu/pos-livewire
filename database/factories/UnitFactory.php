<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            'code' => fake()->unique()->lexify('??'),
            'allows_fractional' => false,
        ];
    }

    /**
     * A unit sold by weight or volume, where fractional quantities are valid.
     */
    public function fractional(): static
    {
        return $this->state(fn (): array => ['allows_fractional' => true]);
    }
}
