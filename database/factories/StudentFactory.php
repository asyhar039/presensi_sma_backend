<?php

namespace Database\Factories;

use App\Enums\Enums\GenderEnums;
use App\Enums\Enums\StudentStatusEnums;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'gender' => fake()->randomElement(GenderEnums::cases()),
            'address' => fake()->address(),
            'status' => fake()->randomElement(StudentStatusEnums::cases()),
        ];
    }
}
