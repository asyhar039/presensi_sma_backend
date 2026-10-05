<?php

namespace Database\Factories;

use App\Enums\LeaveRequestStatusEnum;
use App\Enums\LeaveRequestTypeEnum;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'type' => LeaveRequestTypeEnum::SickLeave,
            'status' => LeaveRequestStatusEnum::Pending,
            'key' => Str::random(16),
            'requested_at' => now(),
        ];
    }
}
