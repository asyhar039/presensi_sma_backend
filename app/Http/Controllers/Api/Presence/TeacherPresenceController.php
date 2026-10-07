<?php

namespace App\Http\Controllers\Api\Presence;

use App\Http\Controllers\Controller;
use App\Services\Presence\PresenceService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Teacher Space - Presence', weight: 14)]
class TeacherPresenceController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private PresenceService $presence) {}

    #[Endpoint(title: 'Current presence state', description: 'Auto-detected active slot: session status, QR value/expiry, live feed and analytics.')]
    public function current(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->presence->current($request->user()),
            'Presence state retrieved successfully.'
        );
    }

    #[Endpoint(title: 'Start presence session', description: 'Opens one session for the active slot; invalid outside it or when already started/stopped.')]
    public function start(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->presence->start($request->user()),
            'Presence session started successfully.',
            201
        );
    }

    #[Endpoint(title: 'Stop presence session', description: 'Closes the session permanently for today; start is then blocked.')]
    public function stop(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->presence->stop($request->user()),
            'Presence session stopped successfully.'
        );
    }

    #[Endpoint(title: 'Refresh QR code', description: 'Rotates the QR key when the 5-minute window lapses; safe to poll.')]
    public function refresh(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->presence->refreshQr($request->user()),
            'QR code refreshed successfully.'
        );
    }
}
