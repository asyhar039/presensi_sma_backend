<?php

namespace App\Http\Controllers\Api\ClassSchedule;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClassSchedule\ListClassScheduleRequest;
use App\Http\Requests\ClassSchedule\StoreClassScheduleRequest;
use App\Http\Requests\ClassSchedule\UpdateClassScheduleRequest;
use App\Http\Resources\ClassScheduleResource;
use App\Models\ClassSchedule;
use App\Services\ClassSchedule\ClassScheduleService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Class Schedules', weight: 9)]
class ClassScheduleController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private ClassScheduleService $schedules) {}

    /**
     * List class schedules for a classroom day.
     */
    #[Endpoint(title: 'List class schedules', description: 'Returns schedules for a classroom on a given day ordered by period, without pagination.')]
    #[QueryParameter('classroom_id', description: 'Classroom id.', type: 'int')]
    #[QueryParameter('day', description: 'Day: monday-sunday.', type: 'string')]
    public function index(ListClassScheduleRequest $request): JsonResponse
    {
        $schedules = $this->schedules->list($request->classroomId(), $request->day());

        return $this->successResponse(
            data: ClassScheduleResource::collection($schedules),
            message: 'Class schedules retrieved successfully.'
        );
    }

    /**
     * Create a class schedule.
     */
    #[Endpoint(title: 'Create class schedule', description: 'Creates a schedule slot; start/end must match the day schedule slot and the day must not be a holiday.')]
    public function store(StoreClassScheduleRequest $request): JsonResponse
    {
        $this->schedules->create($request->validated());

        return $this->successResponse(message: 'Class schedule created successfully.', code: Response::HTTP_CREATED);
    }

    /**
     * Update a class schedule.
     */
    #[Endpoint(title: 'Update class schedule', description: 'Updates a schedule slot with the same holiday and slot validation as creation.')]
    public function update(UpdateClassScheduleRequest $request, ClassSchedule $classSchedule): JsonResponse
    {
        $this->schedules->update($classSchedule, $request->validated());

        return $this->successResponse(message: 'Class schedule updated successfully.');
    }

    /**
     * Delete a class schedule.
     */
    #[Endpoint(title: 'Delete class schedule', description: 'Deletes a class schedule by id.')]
    public function destroy(ClassSchedule $classSchedule): JsonResponse
    {
        $this->schedules->delete($classSchedule);

        return $this->successResponse(message: 'Class schedule deleted successfully.');
    }
}
