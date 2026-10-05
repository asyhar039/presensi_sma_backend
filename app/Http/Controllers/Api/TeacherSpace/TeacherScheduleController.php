<?php

namespace App\Http\Controllers\Api\TeacherSpace;

use App\Http\Controllers\Controller;
use App\Http\Requests\TeacherSpace\TeacherScheduleRequest;
use App\Http\Resources\TeacherSpace\TeacherScheduleResource;
use App\Models\ClassSchedule;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Teacher Space - Subject', weight: 13)]
class TeacherScheduleController extends Controller
{
    use ApiResponseTrait;

    #[Endpoint(title: 'List my teaching schedule', description: 'Schedules assigned to the authenticated teacher on a given day, ordered by start time, without pagination.')]
    #[QueryParameter('day', description: 'Day: monday-sunday.', type: 'string')]
    public function index(TeacherScheduleRequest $request): JsonResponse
    {
        $teacherId = $request->user()->teacher?->id;
        abort_if($teacherId === null, Response::HTTP_NOT_FOUND, 'Teacher profile not found.');

        $schedules = ClassSchedule::query()
            ->with(['classroom', 'teacher.subjects' => fn ($query) => $query->orderBy('name')])
            ->where('teacher_id', $teacherId)
            ->where('day', $request->day()->value)
            ->orderBy('start_time')
            ->orderBy('period')
            ->get();

        return $this->successResponse(
            data: TeacherScheduleResource::collection($schedules),
            message: 'Teaching schedules retrieved successfully.'
        );
    }
}
