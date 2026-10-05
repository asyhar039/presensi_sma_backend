<?php

namespace App\Http\Controllers\Api\TeacherSpace;

use App\Enums\LeaveRequestStepEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\TeacherSpace\DecideLeaveRequestRequest;
use App\Http\Resources\StudentSpace\LeaveRequestResource;
use App\Http\Resources\TeacherSpace\TeacherLeaveRequestResource;
use App\Services\LeaveRequest\LeaveRequestApprovalService;
use App\Services\TeacherSpace\TeacherLeaveListService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Teacher Space - Subject', weight: 13)]
class SubjectLeaveController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private TeacherLeaveListService $leaves,
        private LeaveRequestApprovalService $approvals,
    ) {}

    #[Endpoint(title: 'List subject early out requests', description: 'Today early_out requests in classes you are currently scheduled to teach. Search by student name or identity number; filter by status.')]
    #[QueryParameter('page', description: 'Current page number.', type: 'int', default: 1)]
    #[QueryParameter('per_page', description: 'Items per page (max 50).', type: 'int', default: 10)]
    #[QueryParameter('search', description: 'Search by student name or identity number.', type: 'string')]
    #[QueryParameter('status', description: 'Filter by status: pending, approved, rejected.', type: 'string')]
    #[QueryParameter('sortBy', description: 'Sort column: id, requested_at, date.', type: 'string')]
    #[QueryParameter('order', description: 'Sort direction: asc or desc.', type: 'string')]
    public function index(Request $request): JsonResponse
    {
        ['query' => $query] = $this->leaves->subjectEarlyOutQuery($request->user());
        abort_if($query === null, 404, 'No class is scheduled for you right now.');

        $paginator = $this->leaves->paginate($request, $query, ['todayOnly' => true]);

        return $this->paginatedResponse(
            $paginator,
            TeacherLeaveRequestResource::collection($paginator->items()),
            'Subject leave requests retrieved successfully.'
        );
    }

    #[Endpoint(title: 'Get subject early out detail', description: 'One today early_out request from your current class with full detail and approval trail.')]
    public function show(Request $request, int $leaveRequest): JsonResponse
    {
        $leave = $this->leaves->findForSubjectTeacher($request->user(), $leaveRequest);

        return $this->successResponse(
            data: LeaveRequestResource::make($leave->loadMissing(['user.student', 'approvedBy', 'rejectedBy'])),
            message: 'Leave request retrieved successfully.'
        );
    }

    #[Endpoint(title: 'Decide early out first (subject)', description: 'First approval for an early_out request as the scheduled subject teacher; then it moves to the duty teacher.')]
    public function decide(DecideLeaveRequestRequest $request, int $leaveRequest): JsonResponse
    {
        $leave = $this->leaves->findForSubjectTeacher($request->user(), $leaveRequest);

        $decided = $this->approvals->decide(
            $leave,
            $request->user(),
            LeaveRequestStepEnum::SubjectTeacher,
            $request->string('decision')->toString(),
            $request->input('notes')
        );

        return $this->successResponse(
            data: LeaveRequestResource::make($decided),
            message: 'Leave request decided successfully.'
        );
    }
}
