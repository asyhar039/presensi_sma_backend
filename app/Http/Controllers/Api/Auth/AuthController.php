<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\AuthInformationResource;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private AuthService $authService) {}

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

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse(message: 'Logged out successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->getAuthenticatedUser($request->user());

        return $this->successResponse(
            data: UserResource::make($user),
            message: 'Authenticated user retrieved successfully.'
        );
    }

    public function information(Request $request): JsonResponse
    {
        $information = $this->authService->getInformation($request->user());

        return $this->successResponse(
            data: AuthInformationResource::make($information),
            message: 'User information retrieved successfully.'
        );
    }
}
