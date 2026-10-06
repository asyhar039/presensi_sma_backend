<?php

namespace App\Http\Controllers\Api\Presence;

use App\Http\Controllers\Controller;
use App\Http\Requests\Presence\PresenceHistoryRequest;
use App\Http\Requests\Presence\ScanPresenceRequest;
use App\Services\Presence\PresenceService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Student Space - Presence', weight: 10)]
class StudentPresenceController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private PresenceService $presence) {}

    #[Endpoint(title: 'Eligibility now', description: 'Current slot, session status and whether this student may scan now.')]
    public function current(Request $request): JsonResponse
    {
        $row = $this->presence->studentNow($request->user())
            ?? ['schedule' => null, 'is_within_time' => false, 'has_session' => false, 'is_started' => false, 'is_stopped' => false, 'qr_expired' => true, 'qr_value' => null, 'qr_expires_at' => null];

        return $this->successResponse($row, 'Presence eligibility retrieved successfully.');
    }

    #[Endpoint(title: 'Scan QR presence', description: 'One scan per session; validates slot window, enrolment and school zone.')]
    public function scan(ScanPresenceRequest $request): JsonResponse
    {
        return $this->successResponse(
            $this->presence->scan(
                $request->user(), $request->string('key')->toString(),
                $request->input('latitude') !== null ? (float) $request->input('latitude') : null,
                $request->input('longitude') !== null ? (float) $request->input('longitude') : null,
            ),
            'Presence recorded successfully.',
            201
        );
    }

    #[Endpoint(title: 'Monthly presence calendar', description: 'Per-day schedule entries with present/sick_leave/permit/alpha/holiday status.')]
    public function history(PresenceHistoryRequest $request): JsonResponse
    {
        return $this->successResponse(
            $this->presence->monthlyHistory($request->user(), $request->string('month')->toString()),
            'Presence history retrieved successfully.'
        );
    }
}
