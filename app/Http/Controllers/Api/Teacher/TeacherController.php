<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\ChangeTeacherPasswordRequest;
use App\Http\Requests\Teacher\StoreTeacherRequest;
use App\Http\Requests\Teacher\UpdateTeacherRequest;
use App\Http\Resources\TeacherResource;
use App\Models\Teacher;
use App\Services\Teacher\TeacherService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('Teachers', weight: 6)]
class TeacherController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private TeacherService $teacherService) {}

    /**
     * List teachers with pagination.
     */
    #[Endpoint(title: 'List teachers', description: 'Returns paginated teachers with their user accounts. Supports search by name, email or identity number.')]
    #[QueryParameter('per_page', description: 'Items per page.', type: 'int', default: 15)]
    #[QueryParameter('search', description: 'Search by teacher name, email or identity number.', type: 'string')]
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->teacherService->paginate(
            (int) $request->integer('per_page', 15),
            $request->string('search')->toString() ?: null
        );

        return $this->successResponse(
            data: TeacherResource::collection($paginator->items()),
            message: 'Teachers retrieved successfully.',
            code: Response::HTTP_OK,
            meta: $this->paginationMeta($paginator)
        );
    }

    /**
     * Create a new teacher with a user account.
     */
    #[Endpoint(title: 'Create teacher', description: 'Creates a user account with the teacher role plus the teacher profile.')]
    public function store(StoreTeacherRequest $request): JsonResponse
    {
        $teacher = $this->teacherService->create($request->validated());

        return $this->successResponse(
            data: TeacherResource::make($teacher),
            message: 'Teacher created successfully.',
            code: Response::HTTP_CREATED
        );
    }

    /**
     * Show a single teacher.
     */
    #[Endpoint(title: 'Show teacher', description: 'Returns a single teacher with the linked user account.')]
    public function show(Teacher $teacher): JsonResponse
    {
        $teacher->load('user');

        return $this->successResponse(
            data: TeacherResource::make($teacher),
            message: 'Teacher retrieved successfully.'
        );
    }

    /**
     * Update a teacher and the linked user account.
     */
    #[Endpoint(title: 'Update teacher', description: 'Updates the teacher profile and the linked user account fields.')]
    public function update(UpdateTeacherRequest $request, Teacher $teacher): JsonResponse
    {
        $teacher = $this->teacherService->update($teacher, $request->validated());

        return $this->successResponse(
            data: TeacherResource::make($teacher),
            message: 'Teacher updated successfully.'
        );
    }

    /**
     * Delete a teacher and the linked user account.
     */
    #[Endpoint(title: 'Delete teacher', description: 'Deletes the teacher profile and the linked user account.')]
    public function destroy(Teacher $teacher): JsonResponse
    {
        $this->teacherService->delete($teacher);

        return $this->successResponse(message: 'Teacher deleted successfully.');
    }

    /**
     * Change the password of a teacher account.
     */
    #[Endpoint(title: 'Change teacher password', description: 'Sets a new password for the user account linked to the teacher.')]
    public function changePassword(ChangeTeacherPasswordRequest $request, Teacher $teacher): JsonResponse
    {
        $teacher = $this->teacherService->changePassword($teacher, $request->validated()['password']);

        return $this->successResponse(
            data: TeacherResource::make($teacher),
            message: 'Teacher password changed successfully.'
        );
    }
}
