<?php

namespace App\Http\Resources;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps the authenticated user plus their role detail profile.
 */
class AuthInformationResource extends JsonResource
{
    /**
     * @param  array{user: mixed, profile: mixed, role: string|null}  $resource
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{user: User, profile: Student|Teacher|null, role: string|null} $data */
        $data = $this->resource;

        $profile = match (true) {
            $data['profile'] instanceof Student => StudentResource::make($data['profile']),
            $data['profile'] instanceof Teacher => TeacherResource::make($data['profile']),
            default => null,
        };

        return [
            'user' => UserResource::make($data['user']),
            'role' => $data['role'],
            'profile' => $profile,
            'profile_type' => match (true) {
                $data['profile'] instanceof Student => 'student',
                $data['profile'] instanceof Teacher => 'teacher',
                default => null,
            },
        ];
    }
}
