<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\Profile\ProfileService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('Profile', weight: 1)]
class ProfileController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private ProfileService $profileService) {}

    /**
     * Update the authenticated user's profile.
     */
    #[Endpoint(title: 'Update profile', description: 'Updates name, email, identity number and phone number of the authenticated user.')]
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->profileService->updateProfile($request->user(), $request->profileData());

        return $this->successResponse(
            data: UserResource::make($user),
            message: 'Profile updated successfully.'
        );
    }

    /**
     * Change the authenticated user's password.
     */
    #[Endpoint(title: 'Change password', description: 'Verifies the current password and sets a new password.')]
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $this->profileService->updatePassword($request->user(), $request->string('password')->toString());

        return $this->successResponse(message: 'Password changed successfully.', code: Response::HTTP_OK);
    }

    /**
     * Get the authenticated user's profile.
     */
    #[Endpoint(title: 'Show profile', description: 'Returns the authenticated user profile with roles and permissions.')]
    public function show(Request $request): JsonResponse
    {
        return $this->successResponse(
            data: UserResource::make($request->user()->loadMissing(['roles', 'permissions'])),
            message: 'Profile retrieved successfully.'
        );
    }
}
