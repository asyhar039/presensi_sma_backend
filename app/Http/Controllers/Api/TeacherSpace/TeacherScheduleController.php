<?php

namespace App\Http\Controllers\Api\TeacherSpace;

use App\Enums\DayEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\TeacherSpace\TeacherScheduleResource;
use App\Models\ClassSchedule;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('Teacher Space - Subject', weight: 13)]
class TeacherScheduleController extends Controller
{
    use ApiResponseTrait;

    #[Endpoint(title: 'List my teaching schedule', description: 'All schedules assigned to the authenticated teacher grouped by day (monday-sunday), ordered by start time, without pagination.')]
    public function index(Request $request): JsonResponse
    {
        $teacherId = $request->user()->teacher?->id;
        abort_if($teacherId === null, Response::HTTP_NOT_FOUND, 'Teacher profile not found.');

        $grouped = ClassSchedule::query()
            ->with(['classroom', 'teacher.subjects' => fn ($query) => $query->orderBy('name')])
            ->where('teacher_id', $teacherId)
            ->orderBy('start_time')
            ->orderBy('period')
            ->get()
            ->groupBy(fn (ClassSchedule $schedule): string => $schedule->day->value);

        $data = [];
        foreach (DayEnum::cases() as $day) {
            $data[$day->value] = $grouped->get($day->value, collect())
                ->map(fn (ClassSchedule $schedule): array => TeacherScheduleResource::make($schedule)->resolve($request))
                ->all();
        }

        return $this->successResponse($data, 'Teaching schedules retrieved successfully.');
    }
}
