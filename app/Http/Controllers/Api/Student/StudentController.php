<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\ChangeStudentPasswordRequest;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Services\Student\StudentService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('Students', weight: 5)]
class StudentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private StudentService $studentService) {}

    /**
     * List students with pagination.
     */
    #[Endpoint(title: 'List students', description: 'Returns paginated students with their user accounts. Supports search by name, email or identity number.')]
    #[QueryParameter('per_page', description: 'Items per page.', type: 'int', default: 15)]
    #[QueryParameter('search', description: 'Search by student name, email or identity number.', type: 'string')]
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->studentService->paginate(
            (int) $request->integer('per_page', 15),
            $request->string('search')->toString() ?: null
        );

        return $this->successResponse(
            data: StudentResource::collection($paginator->items()),
            message: 'Students retrieved successfully.',
            code: Response::HTTP_OK,
            meta: $this->paginationMeta($paginator)
        );
    }

    /**
     * Create a new student with a user account.
     */
    #[Endpoint(title: 'Create student', description: 'Creates a user account with the student role plus the student profile.')]
    public function store(StoreStudentRequest $request): JsonResponse
    {
        $student = $this->studentService->create($request->validated());

        return $this->successResponse(
            data: StudentResource::make($student),
            message: 'Student created successfully.',
            code: Response::HTTP_CREATED
        );
    }

    /**
     * Show a single student.
     */
    #[Endpoint(title: 'Show student', description: 'Returns a single student with the linked user account.')]
    public function show(Student $student): JsonResponse
    {
        $student->load('user');

        return $this->successResponse(
            data: StudentResource::make($student),
            message: 'Student retrieved successfully.'
        );
    }

    /**
     * Update a student and the linked user account.
     */
    #[Endpoint(title: 'Update student', description: 'Updates the student profile and the linked user account fields.')]
    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $student = $this->studentService->update($student, $request->validated());

        return $this->successResponse(
            data: StudentResource::make($student),
            message: 'Student updated successfully.'
        );
    }

    /**
     * Delete a student and the linked user account.
     */
    #[Endpoint(title: 'Delete student', description: 'Deletes the student profile and the linked user account.')]
    public function destroy(Student $student): JsonResponse
    {
        $this->studentService->delete($student);

        return $this->successResponse(message: 'Student deleted successfully.');
    }

    /**
     * Change the password of a student account.
     */
    #[Endpoint(title: 'Change student password', description: 'Sets a new password for the user account linked to the student.')]
    public function changePassword(ChangeStudentPasswordRequest $request, Student $student): JsonResponse
    {
        $student = $this->studentService->changePassword($student, $request->validated()['password']);

        return $this->successResponse(
            data: StudentResource::make($student),
            message: 'Student password changed successfully.'
        );
    }
}
