<?php

namespace App\Http\Resources;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps the authenticated user plus their role detail profile.
 *
 * @mixin \stdClass
 *
 * @property array{user: User, profile: Student|Teacher|null, role: string|null} $resource
 */
class AuthInformationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = match (true) {
            $this->resource['profile'] instanceof Student => StudentResource::make($this->resource['profile']),
            $this->resource['profile'] instanceof Teacher => TeacherResource::make($this->resource['profile']),
            default => null,
        };

        return [
            'user' => UserResource::make($this->resource['user']),
            'role' => $this->resource['role'],
            'profile' => $profile,
            'profile_type' => match (true) {
                $this->resource['profile'] instanceof Student => 'student',
                $this->resource['profile'] instanceof Teacher => 'teacher',
                default => null,
            },
        ];
    }
}
