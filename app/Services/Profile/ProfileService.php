<?php

namespace App\Services\Profile;

use App\Models\User;

class ProfileService
{
    /**
     * Update the user's profile fields.
     *
     * @param  array{name: string, email: string, identity_number: ?string, phone_number: ?string}  $data
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->fill($data);
        $user->save();

        return $user->refresh()->loadMissing(['roles', 'permissions']);
    }

    /**
     * Change the user's password.
     */
    public function updatePassword(User $user, string $newPassword): User
    {
        $user->forceFill(['password' => $newPassword])->save();

        return $user->refresh();
    }
}
