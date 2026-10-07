<?php

namespace Database\Factories;

use App\Models\LeaveRequestApproval;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequestApproval>
 */
class LeaveRequestApprovalFactory extends Factory
{
    protected $model = LeaveRequestApproval::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'step' => 'homeroom',
            'decision' => 'pending',
        ];
    }
}
