<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = $this->faker->numberBetween(2026, 2027);

        $oddStart = $this->faker->dateTimeBetween("{$startYear}-08-01", "{$startYear}-09-15");
        $oddEnd = (clone $oddStart)->modify('+4 months');

        $evenStart = (clone $oddEnd)->modify('+1 week');
        $evenEnd = (clone $evenStart)->modify('+4 months');

        return [
            'odd_start_date' => $oddStart->format('Y-m-d'),
            'odd_end_date' => $oddEnd->format('Y-m-d'),
            'even_start_date' => $evenStart->format('Y-m-d'),
            'even_end_date' => $evenEnd->format('Y-m-d'),
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => true]);
    }
}
