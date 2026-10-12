<?php

namespace App\Http\Controllers\Api\TeacherSpace;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dropdown\TeacherDropdownSelectedRequest;
use App\Http\Requests\TeacherSpace\TeacherStudentHistoryDetailRequest;
use App\Http\Requests\TeacherSpace\TeacherStudentHistoryRequest;
use App\Http\Resources\AcademicYearResource;
use App\Http\Resources\Dropdown\ClassroomDropdownResource;
use App\Http\Resources\TeacherSpace\TeacherStudentHistoryResource;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Services\TeacherSpace\TeacherStudentHistoryService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('Teacher Space - Student History', weight: 14)]
class TeacherStudentHistoryController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private TeacherStudentHistoryService $history) {}

    private function yearOr404(): AcademicYear
    {
        $year = $this->history->activeYear();
        abort_if($year === null, Response::HTTP_NOT_FOUND, 'No active academic year.');

        return $year;
    }

    /** @return array<string, mixed> */
    private function yearPayload(AcademicYear $year): array
    {
        $request = request();

        return array_merge(AcademicYearResource::make($year)->resolve($request), ['label' => $year->label()]);
    }

    #[Endpoint(title: 'My teaching classrooms dropdown', description: 'Paginated classrooms in the active academic year where the teacher has a class schedule.')]
    #[QueryParameter('search', description: 'Search by classroom name.', type: 'string')]
    #[QueryParameter('page', description: 'Current page number.', type: 'int', default: 1)]
    #[QueryParameter('per_page', description: 'Items per page (max 50).', type: 'int', default: 20)]
    public function classroomsDropdown(Request $request): JsonResponse
    {
        $year = $this->yearOr404();
        $paginator = $this->history->paginateTeacherClassrooms($request, $request->user(), $year);

        return $this->paginatedResponse(
            $paginator,
            ClassroomDropdownResource::collection($paginator->items()),
            'Teaching classrooms retrieved successfully.'
        );
    }

    #[Endpoint(title: 'My teaching classrooms dropdown selected', description: 'Resolves {value, label} options for the teacher classrooms, scoped to the active academic year.')]
    #[QueryParameter('active_ids', description: 'IDs to resolve, e.g. ?active_ids[]=1&active_ids[]=2.', type: 'array')]
    public function classroomsSelected(TeacherDropdownSelectedRequest $request): JsonResponse
    {
        $year = $this->yearOr404();
        $allowed = $this->history->teacherClassrooms($request->user(), $year)->keyBy('id');
        $items = $allowed->only($request->activeIds())->values();

        return $this->successResponse(
            ClassroomDropdownResource::collection($items)->resolve($request),
            'Teaching classrooms dropdown selected retrieved successfully.'
        );
    }

    #[Endpoint(title: 'Student attendance history', description: 'All students taught by the teacher in the active academic year with present/sick/permit/alpha counts over effective school days (public holidays and empty schedule days excluded).')]
    #[QueryParameter('page', description: 'Current page number.', type: 'int', default: 1)]
    #[QueryParameter('per_page', description: 'Items per page (max 50).', type: 'int', default: 10)]
    #[QueryParameter('search', description: 'Search by student name or identity number.', type: 'string')]
    #[QueryParameter('classroom_id', description: 'Filter by one of my teaching classrooms.', type: 'int')]
    #[QueryParameter('semester', description: 'Filter by semester: 1/odd or 2/even. Defaults to the full academic year.', type: 'string')]
    #[QueryParameter('sortBy', description: 'Sort column: id, name, created_at.', type: 'string')]
    #[QueryParameter('order', description: 'Sort direction: asc or desc.', type: 'string')]
    public function index(TeacherStudentHistoryRequest $request): JsonResponse
    {
        $year = $this->yearOr404();
        $ids = $this->history->teacherClassrooms($request->user(), $year)->pluck('id')->all();
        abort_if($ids === [], Response::HTTP_NOT_FOUND, 'You have no teaching classrooms in the active academic year.');

        if ($request->filled('classroom_id') && ! in_array((int) $request->integer('classroom_id'), $ids, true)) {
            abort(Response::HTTP_NOT_FOUND, 'Classroom not found in your teaching classrooms.');
        }

        $paginator = $this->history->paginateStudents($request, $year, $ids);

        return $this->paginatedResponse(
            $paginator,
            TeacherStudentHistoryResource::collection($paginator->items())->resolve($request),
            'Student attendance history retrieved successfully.'
        );
    }

    #[Endpoint(title: 'Student attendance detail', description: 'One taught student scoped to the teacher\u0027s own classroom/schedules: year-range attendance summary plus a monthly calendar of the teacher\u0027s slots only.')]
    #[QueryParameter('month', description: 'Month number 1-12. Defaults to current month.', type: 'int')]
    #[QueryParameter('year', description: 'Year, e.g. 2026. Defaults to current year.', type: 'int')]
    public function show(TeacherStudentHistoryDetailRequest $request, Student $student): JsonResponse
    {
        $year = $this->yearOr404();
        $ids = $this->history->teacherClassrooms($request->user(), $year)->pluck('id')->all();
        $found = $this->history->findScopedStudent($request->user(), $year, $ids, $student->id);
        abort_if($found === null, Response::HTTP_NOT_FOUND, 'Student not found in your teaching classrooms.');

        [$month, $yearNum] = $request->monthYear();
        $detail = $this->history->monthlyDetail($found, $request->user(), $year, $ids, $yearNum, $month);

        return $this->successResponse([
            'academic_year' => $this->yearPayload($year),
            'student' => TeacherStudentHistoryResource::make($found->setAttribute('attendance', $detail['summary']))->resolve($request),
            'attendance' => $detail['summary'],
            'month' => $detail['calendar']['month'],
            'calendar' => $detail['calendar']['days'],
        ], 'Student attendance detail retrieved successfully.');
    }
}
