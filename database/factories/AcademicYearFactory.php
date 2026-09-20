<?php

namespace Database\Factories;

use App\Enums\Enums\SemesterEnums;
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
        $start = fake()->dateTimeBetween('-2 years', 'now');

        return [
            'start_date' => $start->format('Y-m-d'),
            'end_date' => (clone $start)->modify('+1 year')->format('Y-m-d'),
            'semester' => fake()->randomElement(SemesterEnums::cases()),
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => true]);
    }
}
