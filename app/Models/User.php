<?php

namespace App\Models;

use App\Enums\RoleEnum;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property RoleEnum $role
 */
#[Fillable(['identity_number', 'name', 'email', 'phone_number', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
#[CollectedBy(Collection::class)]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => RoleEnum::class,
        ];
    }

    /**
     * Determine whether the user holds the given role.
     *
     * @param  string|RoleEnum|iterable<string|RoleEnum>  $roles
     */
    public function hasRole(string|RoleEnum|iterable $roles): bool
    {
        $roles = $roles instanceof RoleEnum || is_string($roles) ? [$roles] : $roles;

        foreach ($roles as $role) {
            $role = $role instanceof RoleEnum ? $role : RoleEnum::tryFrom(strtolower(trim($role)));

            if ($role !== null && $this->role === $role) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user holds any of the given roles.
     *
     * @param  string|RoleEnum|iterable<string|RoleEnum>  $roles
     */
    public function hasAnyRole(string|RoleEnum|iterable $roles): bool
    {
        return $this->hasRole($roles);
    }

    public function isAdmin(): bool
    {
        return $this->role === RoleEnum::Admin;
    }

    public function isTeacher(): bool
    {
        return $this->role === RoleEnum::Teacher;
    }

    public function isStudent(): bool
    {
        return $this->role === RoleEnum::Student;
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    #[Scope]
    protected function ofRole(Builder $query, string|RoleEnum $role): Builder
    {
        $role = $role instanceof RoleEnum ? $role : RoleEnum::tryFrom(strtolower(trim($role)));

        return $query->when(
            $role instanceof RoleEnum,
            fn (Builder $query): Builder => $query->where('role', $role->value),
        );
    }

    /**
     * @return HasOne<Student, $this>
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /**
     * @return HasOne<Teacher, $this>
     */
    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }
}
