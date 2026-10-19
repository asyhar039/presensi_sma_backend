<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\AttendanceRecapFilterRequest;
use App\Models\Student;
use App\Services\Attendance\AttendanceRecapService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;

#[Group('Attendance Recap', weight: 15)]
class AttendanceRecapController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private AttendanceRecapService $recapService) {}

    /**
     * Top summary cards for attendance metrics.
     */
    #[Endpoint(title: 'Attendance summary', description: 'Returns top metrics summary: total students, average attendance rate, total present, permission, sick, and absent.')]
    #[QueryParameter('classroom_id', description: 'Classroom ID filter.', type: 'int')]
    #[QueryParameter('academic_year_id', description: 'Academic year ID filter. Defaults to active year.', type: 'int')]
    #[QueryParameter('semester', description: 'Semester filter: 1/odd or 2/even.', type: 'string')]
    #[QueryParameter('month', description: 'Month filter 1-12.', type: 'int')]
    public function summary(AttendanceRecapFilterRequest $request): JsonResponse
    {
        $summary = $this->recapService->getSummary($request);

        return $this->successResponse(
            data: $summary,
            message: 'Attendance summary retrieved successfully'
        );
    }

    /**
     * Paginated list of student summaries in the classroom.
     */
    #[Endpoint(title: 'Student summaries', description: 'Returns paginated list of students in the classroom with present, permission, sick, absent counts and attendance rate.')]
    #[QueryParameter('classroom_id', description: 'Classroom ID filter.', type: 'int')]
    #[QueryParameter('academic_year_id', description: 'Academic year ID filter. Defaults to active year.', type: 'int')]
    #[QueryParameter('semester', description: 'Semester filter: 1/odd or 2/even.', type: 'string')]
    #[QueryParameter('month', description: 'Month filter 1-12.', type: 'int')]
    #[QueryParameter('page', description: 'Current page number.', type: 'int', default: 1)]
    #[QueryParameter('per_page', description: 'Items per page (max 100).', type: 'int', default: 10)]
    #[QueryParameter('search', description: 'Search by student name or NIS.', type: 'string')]
    #[QueryParameter('sortBy', description: 'Sort column: student_name, present, permission, sick, absent, total, attendance_rate.', type: 'string')]
    #[QueryParameter('order', description: 'Sort direction: asc or desc.', type: 'string')]
    public function studentSummaries(AttendanceRecapFilterRequest $request): JsonResponse
    {
        $paginator = $this->recapService->paginateStudentSummaries($request);

        return $this->paginatedResponse(
            paginator: $paginator,
            data: $paginator->items(),
            message: 'Student summaries retrieved successfully'
        );
    }

    /**
     * Detailed attendance history records for a single student.
     */
    #[Endpoint(title: 'Student attendance detail', description: 'Returns detailed session records and attendance summary for a specific student.')]
    #[QueryParameter('classroom_id', description: 'Classroom ID filter.', type: 'int')]
    #[QueryParameter('academic_year_id', description: 'Academic year ID filter. Defaults to active year.', type: 'int')]
    #[QueryParameter('semester', description: 'Semester filter: 1/odd or 2/even.', type: 'string')]
    #[QueryParameter('month', description: 'Month filter 1-12.', type: 'int')]
    public function studentDetail(AttendanceRecapFilterRequest $request, Student $student): JsonResponse
    {
        $detail = $this->recapService->getStudentDetail($student->id, $request);

        return $this->successResponse(
            data: $detail,
            message: 'Student attendance detail retrieved successfully'
        );
    }
}

