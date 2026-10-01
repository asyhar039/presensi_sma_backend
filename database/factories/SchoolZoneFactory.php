<?php

namespace Database\Factories;

use App\Models\SchoolZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolZone>
 */
class SchoolZoneFactory extends Factory
{
    protected $model = SchoolZone::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->bothify('Zone-###'),
            'points' => [
                [(float) fake()->latitude(-7, -6), (float) fake()->longitude(106, 107)],
                [(float) fake()->latitude(-7, -6), (float) fake()->longitude(106, 107)],
                [(float) fake()->latitude(-7, -6), (float) fake()->longitude(106, 107)],
            ],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
