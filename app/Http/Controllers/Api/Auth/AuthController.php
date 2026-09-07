<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\AuthInformationResource;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Auth', weight: 0)]
class AuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private AuthService $authService) {}

    /**
     * Log in with email and password and receive a Sanctum bearer token.
     */
    #[Endpoint(title: 'Login', description: 'Validates credentials and issues a Sanctum personal access token.')]
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->credentials());

        return $this->successResponse(
            data: [
                'token' => $result['token'],
                'token_type' => 'Bearer',
                'user' => UserResource::make($result['user']),
            ],
            message: 'Login successful.'
        );
    }

    /**
     * Revoke the current access token.
     */
    #[Endpoint(title: 'Logout', description: 'Revokes the bearer token used for the current request.')]
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse(message: 'Logged out successfully.');
    }

    /**
     * Get the currently authenticated user.
     */
    #[Endpoint(title: 'Authenticated user', description: 'Returns the bearer-token owner with roles and permissions.')]
    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->getAuthenticatedUser($request->user());

        return $this->successResponse(
            data: UserResource::make($user),
            message: 'Authenticated user retrieved successfully.'
        );
    }

    /**
     * Get the authenticated user plus role profile details.
     */
    #[Endpoint(title: 'User information', description: 'Returns the user, their active role, and the student or teacher profile when present.')]
    public function information(Request $request): JsonResponse
    {
        $information = $this->authService->getInformation($request->user());

        return $this->successResponse(
            data: AuthInformationResource::make($information),
            message: 'User information retrieved successfully.'
        );
    }
}
