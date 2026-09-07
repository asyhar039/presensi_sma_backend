<?php

namespace App\Services\Auth;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Attempt login and issue a Sanctum token.
     *
     * @param  array{email: string, password: string}  $credentials
     * @return array{user: User, token: string}
     *
     * @throws ValidationException
     */
    public function login(array $credentials): array
    {
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user->loadMissing(['roles', 'permissions']);

        $token = $user->createToken('auth-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Logout the user by revoking the current access token.
     *
     * @param  User  $user
     * @return void
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    /**
     * Get the authenticated user with their roles, permissions, and profile.
     *
     * @param  User  $user
     * @return User
     */
    public function getAuthenticatedUser(User $user): User
    {
        return $user->loadMissing(['roles', 'permissions', 'student', 'teacher']);
    }

    /**
     * @return array{user: User, profile: Student|Teacher|null, role: string|null}
     */
    public function getInformation(User $user): array
    {
        $user->loadMissing(['roles', 'permissions', 'student', 'teacher']);

        $profile = $user->student ?? $user->teacher;

        return [
            'user' => $user,
            'profile' => $profile,
            'role' => $user->getRoleNames()->first(),
        ];
    }
}
