<?php

namespace App\Http\Controllers\Api\Classroom;

use App\Http\Controllers\Controller;
use App\Http\Requests\Classroom\AssignClassroomStudentsRequest;
use App\Http\Requests\Classroom\AssignHomeroomRequest;
use App\Http\Requests\Classroom\StoreClassroomRequest;
use App\Http\Requests\Classroom\UpdateClassroomRequest;
use App\Http\Resources\ClassroomResource;
use App\Http\Resources\StudentResource;
use App\Models\Classroom;
use App\Models\Student;
use App\Services\Classroom\ClassroomService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('Classrooms', weight: 7)]
class ClassroomController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private ClassroomService $classroomService) {}

    /**
     * List classrooms with pagination.
     */
    #[Endpoint(title: 'List classrooms', description: 'Returns paginated classrooms with academic year, homeroom teacher and student counts. Filter by academic_year_id when needed.')]
    #[QueryParameter('per_page', description: 'Items per page.', type: 'int', default: 10)]
    #[QueryParameter('academic_year_id', description: 'Filter classrooms by academic year.', type: 'int')]
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->classroomService->paginate(
            (int) $request->integer('per_page', 10),
            $request->integer('academic_year_id') ?: null
        );

        return $this->successResponse(
            data: ClassroomResource::collection($paginator->items()),
            message: 'Classrooms retrieved successfully.',
            code: Response::HTTP_OK,
            meta: $this->paginationMeta($paginator)
        );
    }

    /**
     * Create a new classroom.
     */
    #[Endpoint(title: 'Create classroom', description: 'Creates a classroom. academic_year_id is optional and falls back to the active academic year.')]
    public function store(StoreClassroomRequest $request): JsonResponse
    {
        $classroom = $this->classroomService->create($request->validated());

        return $this->successResponse(
            data: ClassroomResource::make($classroom),
            message: 'Classroom created successfully.',
            code: Response::HTTP_CREATED
        );
    }

    /**
     * Show a single classroom.
     */
    #[Endpoint(title: 'Show classroom', description: 'Returns a single classroom with academic year, homeroom teacher and students.')]
    public function show(Classroom $classroom): JsonResponse
    {
        $classroom->load(['academicYear', 'homeroomTeacher.user', 'students.user']);

        return $this->successResponse(
            data: ClassroomResource::make($classroom),
            message: 'Classroom retrieved successfully.'
        );
    }

    /**
     * Update a classroom.
     */
    #[Endpoint(title: 'Update classroom', description: 'Updates classroom name, academic year or homeroom teacher.')]
    public function update(UpdateClassroomRequest $request, Classroom $classroom): JsonResponse
    {
        $classroom = $this->classroomService->update($classroom, $request->validated());

        return $this->successResponse(
            data: ClassroomResource::make($classroom),
            message: 'Classroom updated successfully.'
        );
    }

    /**
     * Delete a classroom.
     */
    #[Endpoint(title: 'Delete classroom', description: 'Deletes a classroom by id. Student memberships are removed.')]
    public function destroy(Classroom $classroom): JsonResponse
    {
        $this->classroomService->delete($classroom);

        return $this->successResponse(message: 'Classroom deleted successfully.');
    }

    /**
     * Assign a homeroom teacher to a classroom.
     */
    #[Endpoint(title: 'Assign homeroom teacher', description: 'Assigns a teacher as homeroom teacher. A teacher can only be homeroom of one classroom per academic year. Pass null to unassign.')]
    public function assignHomeroom(AssignHomeroomRequest $request, Classroom $classroom): JsonResponse
    {
        $classroom = $this->classroomService->assignHomeroom($classroom, $request->validated()['homeroom_teacher_id'] ?? null);

        return $this->successResponse(
            data: ClassroomResource::make($classroom),
            message: 'Homeroom teacher assigned successfully.'
        );
    }

    /**
     * Remove the homeroom teacher from a classroom.
     */
    #[Endpoint(title: 'Remove homeroom teacher', description: 'Unassigns the homeroom teacher from the classroom.')]
    public function removeHomeroom(Classroom $classroom): JsonResponse
    {
        $classroom = $this->classroomService->assignHomeroom($classroom, null);

        return $this->successResponse(
            data: ClassroomResource::make($classroom),
            message: 'Homeroom teacher removed successfully.'
        );
    }

    /**
     * List students assigned to a classroom.
     */
    #[Endpoint(title: 'List classroom students', description: 'Returns paginated students assigned to the classroom.')]
    #[QueryParameter('page', description: 'Current page number.', type: 'int', default: 1)]
    #[QueryParameter('per_page', description: 'Items per page.', type: 'int', default: 10)]
    public function students(Request $request, Classroom $classroom): JsonResponse
    {
        $paginator = $this->classroomService->paginateStudents($classroom, (int) $request->integer('per_page', 10));

        return $this->successResponse(
            data: StudentResource::collection($paginator->items()),
            message: 'Classroom students retrieved successfully.',
            code: Response::HTTP_OK,
            meta: $this->paginationMeta($paginator)
        );
    }

    /**
     * Assign students to a classroom.
     */
    #[Endpoint(title: 'Assign students to classroom', description: 'Syncs the given student ids into the classroom. Existing memberships not listed are removed.')]
    public function syncStudents(AssignClassroomStudentsRequest $request, Classroom $classroom): JsonResponse
    {
        $classroom = $this->classroomService->syncStudents($classroom, $request->validated()['student_ids']);

        return $this->successResponse(
            data: ClassroomResource::make($classroom),
            message: 'Students assigned to classroom successfully.'
        );
    }

    /**
     * Remove a student from a classroom.
     */
    #[Endpoint(title: 'Remove student from classroom', description: 'Removes the student membership from the classroom without deleting the student.')]
    public function removeStudent(Classroom $classroom, Student $student): JsonResponse
    {
        $this->classroomService->removeStudent($classroom, $student);

        return $this->successResponse(message: 'Student removed from classroom successfully.');
    }
}
