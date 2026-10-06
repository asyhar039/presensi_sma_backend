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

#[Group('Teacher Space - Duty', weight: 12)]
class DutyLeaveController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private TeacherLeaveListService $leaves,
        private LeaveRequestApprovalService $approvals,
    ) {}

    #[Endpoint(title: 'Get duty status', description: 'Whether you are a duty teacher (active year) and whether the current time is inside your slot.')]
    public function status(Request $request): JsonResponse
    {
        return $this->successResponse(
            data: $this->approvals->dutyStatus($request->user()),
            message: 'Duty status retrieved successfully.'
        );
    }

    #[Endpoint(title: 'List duty leave requests', description: 'Today early_out and late_arrival requests across classes. Search by student name or identity number; filter by status.')]
    #[QueryParameter('page', description: 'Current page number.', type: 'int', default: 1)]
    #[QueryParameter('per_page', description: 'Items per page (max 50).', type: 'int', default: 10)]
    #[QueryParameter('search', description: 'Search by student name or identity number.', type: 'string')]
    #[QueryParameter('status', description: 'Filter by status: pending, approved, rejected.', type: 'string')]
    #[QueryParameter('sortBy', description: 'Sort column: id, requested_at, date.', type: 'string')]
    #[QueryParameter('order', description: 'Sort direction: asc or desc.', type: 'string')]
    public function index(Request $request): JsonResponse
    {
        abort_unless($this->approvals->isDutyTeacherToday($request->user()), 403, 'Only today duty teacher can access.');

        $paginator = $this->leaves->paginate($request, $this->leaves->dutyQuery(), ['todayOnly' => true]);

        return $this->paginatedResponse(
            $paginator,
            TeacherLeaveRequestResource::collection($paginator->items()),
            'Duty leave requests retrieved successfully.'
        );
    }

    #[Endpoint(title: 'Get duty leave request detail', description: 'One today early_out or late_arrival request with full detail and approval trail.')]
    public function show(Request $request, int $leaveRequest): JsonResponse
    {
        $leave = $this->leaves->findForDuty($request->user(), $leaveRequest);

        return $this->successResponse(
            data: LeaveRequestResource::make($leave->loadMissing(['user.student', 'approvedBy', 'rejectedBy'])),
            message: 'Leave request retrieved successfully.'
        );
    }

    #[Endpoint(title: 'Decide late arrival (duty)', description: 'Approve or reject a late_arrival request as duty teacher.')]
    public function decideLateArrival(DecideLeaveRequestRequest $request, int $leaveRequest): JsonResponse
    {
        $leave = $this->leaves->findForDuty($request->user(), $leaveRequest);

        $decided = $this->approvals->decide(
            $leave,
            $request->user(),
            LeaveRequestStepEnum::DutyTeacher,
            $request->string('decision')->toString(),
            $request->input('notes')
        );

        return $this->successResponse(
            data: LeaveRequestResource::make($decided),
            message: 'Leave request decided successfully.'
        );
    }

    #[Endpoint(title: 'Decide early out final (duty)', description: 'Final duty-teacher approval for an early_out request after the subject teacher approved.')]
    public function decideEarlyOut(DecideLeaveRequestRequest $request, int $leaveRequest): JsonResponse
    {
        $leave = $this->leaves->findForDuty($request->user(), $leaveRequest);

        $decided = $this->approvals->decide(
            $leave,
            $request->user(),
            LeaveRequestStepEnum::DutyTeacher,
            $request->string('decision')->toString(),
            $request->input('notes')
        );

        return $this->successResponse(
            data: LeaveRequestResource::make($decided),
            message: 'Leave request decided successfully.'
        );
    }
}
