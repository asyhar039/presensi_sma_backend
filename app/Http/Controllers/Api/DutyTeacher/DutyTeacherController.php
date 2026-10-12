<?php

namespace App\Http\Controllers\Api\DutyTeacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\DutyTeacher\StoreDutyTeacherRequest;
use App\Http\Requests\DutyTeacher\UpdateDutyTeacherRequest;
use App\Http\Resources\DutyTeacherResource;
use App\Models\DutyTeacher;
use App\Services\DutyTeacher\DutyTeacherService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('Duty Teachers', weight: 10)]
class DutyTeacherController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private DutyTeacherService $dutyTeachers) {}

    /**
     * List duty teachers with pagination.
     */
    #[Endpoint(title: 'List duty teachers', description: 'Returns paginated duty teachers. Defaults to the active academic year; pass academic_year_id to scope another year.')]
    #[QueryParameter('page', description: 'Current page number.', type: 'int', default: 1)]
    #[QueryParameter('per_page', description: 'Items per page (max 50).', type: 'int', default: 10)]
    #[QueryParameter('search', description: 'Search by teacher name.', type: 'string')]
    #[QueryParameter('academic_year_id', description: 'Filter by academic year. Defaults to the active academic year.', type: 'int')]
    #[QueryParameter('teacher_id', description: 'Filter by teacher.', type: 'int')]
    #[QueryParameter('day', description: 'Filter by day: monday-sunday.', type: 'string')]
    #[QueryParameter('sortBy', description: 'Sort column: id, day, start_time, created_at.', type: 'string')]
    #[QueryParameter('order', description: 'Sort direction: asc or desc.', type: 'string')]
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->dutyTeachers->paginate($request);

        return $this->paginatedResponse(
            $paginator,
            DutyTeacherResource::collection($paginator->items()),
            'Duty teachers retrieved successfully.'
        );
    }

    /**
     * Create a duty teacher assignment.
     */
    #[Endpoint(title: 'Create duty teacher', description: 'Assigns a teacher to a duty slot. academic_year_id falls back to the active academic year. Overlapping slots for the same teacher are rejected.')]
    public function store(StoreDutyTeacherRequest $request): JsonResponse
    {
        $this->dutyTeachers->create($request->validated());

        return $this->successResponse(
            message: 'Duty teacher created successfully.',
            code: Response::HTTP_CREATED
        );
    }

    /**
     * Show a single duty teacher assignment.
     */
    #[Endpoint(title: 'Show duty teacher', description: 'Returns a single duty teacher assignment by id.')]
    public function show(DutyTeacher $dutyTeacher): JsonResponse
    {
        $dutyTeacher->load(['teacher.user', 'academicYear']);

        return $this->successResponse(
            data: DutyTeacherResource::make($dutyTeacher),
            message: 'Duty teacher retrieved successfully.'
        );
    }

    /**
     * Update a duty teacher assignment.
     */
    #[Endpoint(title: 'Update duty teacher', description: 'Updates a duty teacher assignment; overlapping slots for the same teacher are rejected.')]
    public function update(UpdateDutyTeacherRequest $request, DutyTeacher $dutyTeacher): JsonResponse
    {
        $this->dutyTeachers->update($dutyTeacher, $request->validated());

        return $this->successResponse(message: 'Duty teacher updated successfully.');
    }

    /**
     * Delete a duty teacher assignment.
     */
    #[Endpoint(title: 'Delete duty teacher', description: 'Deletes a duty teacher assignment by id.')]
    public function destroy(DutyTeacher $dutyTeacher): JsonResponse
    {
        $this->dutyTeachers->delete($dutyTeacher);

        return $this->successResponse(message: 'Duty teacher deleted successfully.');
    }
}
