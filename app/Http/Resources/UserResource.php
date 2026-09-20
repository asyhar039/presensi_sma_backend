<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'identity_number' => $this->identity_number,
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'roles' => $this->when(
                $this->relationLoaded('roles') || method_exists($this->resource, 'getRoleNames'),
                fn () => $this->getRoleNames()->values()
            ),
            'permissions' => $this->when(
                $this->relationLoaded('permissions') || method_exists($this->resource, 'getAllPermissions'),
                fn () => $this->getAllPermissions()->pluck('name')->values()
            ),
        ];
    }
}
