<?php

namespace App\Http\Controllers\Api\TeacherSubject;

use App\Http\Controllers\Controller;
use App\Http\Requests\TeacherSubject\StoreTeacherSubjectRequest;
use App\Http\Resources\TeacherSubjectResource;
use App\Models\TeacherSubject;
use App\Services\TeacherSubject\TeacherSubjectService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('Teacher Subjects', weight: 8)]
class TeacherSubjectController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private TeacherSubjectService $teacherSubjectService) {}

    /**
     * List teacher-subject assignments.
     */
    #[Endpoint(title: 'List teacher-subject assignments', description: 'Returns paginated teacher assignments to subjects. academic_year_id is optional; pass it to scope another year.')]
    #[QueryParameter('per_page', description: 'Items per page.', type: 'int', default: 15)]
    #[QueryParameter('teacher_id', description: 'Filter by teacher.', type: 'int')]
    #[QueryParameter('subject_id', description: 'Filter by subject.', type: 'int')]
    #[QueryParameter('academic_year_id', description: 'Filter by academic year.', type: 'int')]
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->teacherSubjectService->paginate(
            (int) $request->integer('per_page', 15),
            $request->integer('teacher_id') ?: null,
            $request->integer('subject_id') ?: null,
            $request->integer('academic_year_id') ?: null,
        );

        return $this->successResponse(
            data: TeacherSubjectResource::collection($paginator->items()),
            message: 'Teacher-subject assignments retrieved successfully.',
            code: Response::HTTP_OK,
            meta: $this->paginationMeta($paginator)
        );
    }

    /**
     * Assign a teacher to a subject.
     */
    #[Endpoint(title: 'Assign teacher to subject', description: 'Assigns a teacher to a subject. academic_year_id is optional and falls back to the active academic year.')]
    public function store(StoreTeacherSubjectRequest $request): JsonResponse
    {
        $assignment = $this->teacherSubjectService->create($request->validated());

        return $this->successResponse(
            data: TeacherSubjectResource::make($assignment),
            message: 'Teacher assigned to subject successfully.',
            code: Response::HTTP_CREATED
        );
    }

    /**
     * Remove a teacher-subject assignment.
     */
    #[Endpoint(title: 'Remove teacher-subject assignment', description: 'Deletes a teacher-subject assignment by id.')]
    public function destroy(TeacherSubject $teacherSubject): JsonResponse
    {
        $this->teacherSubjectService->delete($teacherSubject);

        return $this->successResponse(message: 'Teacher-subject assignment removed successfully.');
    }
}
