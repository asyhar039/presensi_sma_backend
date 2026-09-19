<?php

namespace App\Http\Controllers\Api\Setting;

use App\Enums\DayEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\UpdateDayScheduleRequest;
use App\Http\Resources\Setting\DayScheduleResource;
use App\Services\Setting\ScheduleSettingService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Settings', weight: 10)]
class ScheduleSettingController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private ScheduleSettingService $scheduleSettings) {}

    /**
     * List day schedules for the whole week.
     */
    #[Endpoint(title: 'List day schedules', description: 'Returns the schedule slots for every day, keyed Monday to Sunday. Slots are contiguous with no overlaps or gaps.')]
    public function index(): JsonResponse
    {
        $days = collect($this->scheduleSettings->all())
            ->map(fn (array $schedules, string $day): array => ['day' => $day, 'schedules' => $schedules])
            ->values()
            ->all();

        return $this->successResponse(
            data: DayScheduleResource::collection($days),
            message: 'Day schedules retrieved successfully.'
        );
    }

    /**
     * Show the schedule of a single day.
     */
    #[Endpoint(title: 'Show day schedule', description: 'Returns the schedule slots for one day (monday-sunday).')]
    public function show(string $day): JsonResponse
    {
        $dayEnum = DayEnum::tryFrom(strtolower($day));

        if ($dayEnum === null) {
            return $this->errorResponse(
                message: 'Day not found.',
                code: Response::HTTP_NOT_FOUND
            );
        }

        return $this->successResponse(
            data: DayScheduleResource::make([
                'day' => $dayEnum->value,
                'schedules' => $this->scheduleSettings->getDay($dayEnum->value),
            ]),
            message: 'Day schedule retrieved successfully.'
        );
    }

    /**
     * Create or update the schedule of a day.
     */
    #[Endpoint(title: 'Upsert day schedule', description: 'Replaces the schedule slots of one day. Slots must be ordered end-to-start with no overlaps and no gaps.')]
    public function update(UpdateDayScheduleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $schedules = $this->scheduleSettings->updateDay($validated['day'], $validated['schedules']);

        return $this->successResponse(
            data: DayScheduleResource::make(['day' => $validated['day'], 'schedules' => $schedules]),
            message: 'Day schedule updated successfully.'
        );
    }
}
