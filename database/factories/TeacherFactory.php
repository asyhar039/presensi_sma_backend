<?php

namespace Database\Factories;

use App\Enums\Enums\GenderEnums;
use App\Enums\Enums\TeacherEmploymentStatusEnums;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'gender' => fake()->randomElement(GenderEnums::cases()),
            'address' => fake()->address(),
            'employment_status' => fake()->randomElement(TeacherEmploymentStatusEnums::cases()),
        ];
    }
}
