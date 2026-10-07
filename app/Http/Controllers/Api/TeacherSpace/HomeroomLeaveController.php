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

#[Group('Teacher Space - Homeroom', weight: 11)]
class HomeroomLeaveController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private TeacherLeaveListService $leaves,
        private LeaveRequestApprovalService $approvals,
    ) {}

    #[Endpoint(title: 'List homeroom leave requests', description: 'Leave requests of students in your active-year homeroom class. Search by student name or identity number; filter by type and status.')]
    #[QueryParameter('page', description: 'Current page number.', type: 'int', default: 1)]
    #[QueryParameter('per_page', description: 'Items per page (max 50).', type: 'int', default: 10)]
    #[QueryParameter('search', description: 'Search by student name or identity number.', type: 'string')]
    #[QueryParameter('type', description: 'Filter by type: sick_leave, early_out, late_arrival.', type: 'string')]
    #[QueryParameter('status', description: 'Filter by status: pending, approved, rejected.', type: 'string')]
    #[QueryParameter('sortBy', description: 'Sort column: id, requested_at, date.', type: 'string')]
    #[QueryParameter('order', description: 'Sort direction: asc or desc.', type: 'string')]
    public function index(Request $request): JsonResponse
    {
        $query = $this->leaves->homeroomQuery($request->user());
        abort_if($query === null, 404, 'You are not assigned as a homeroom teacher in the active academic year.');

        $paginator = $this->leaves->paginate($request, $query);

        return $this->paginatedResponse(
            $paginator,
            TeacherLeaveRequestResource::collection($paginator->items()),
            'Homeroom leave requests retrieved successfully.'
        );
    }

    #[Endpoint(title: 'Get homeroom leave request detail', description: 'One leave request from your homeroom class with full detail and approval trail.')]
    public function show(Request $request, int $leaveRequest): JsonResponse
    {
        $leave = $this->leaves->findForHomeroom($request->user(), $leaveRequest);

        return $this->successResponse(
            data: LeaveRequestResource::make($leave->loadMissing(['user.student', 'approvedBy', 'rejectedBy'])),
            message: 'Leave request retrieved successfully.'
        );
    }

    #[Endpoint(title: 'Decide sick leave (homeroom)', description: 'Approve or reject a sick_leave request as homeroom teacher. Reject accepts an optional note.')]
    public function decide(DecideLeaveRequestRequest $request, int $leaveRequest): JsonResponse
    {
        $leave = $this->leaves->findForHomeroom($request->user(), $leaveRequest);

        $decided = $this->approvals->decide(
            $leave,
            $request->user(),
            LeaveRequestStepEnum::Homeroom,
            $request->string('decision')->toString(),
            $request->input('notes')
        );

        return $this->successResponse(
            data: LeaveRequestResource::make($decided),
            message: 'Leave request decided successfully.'
        );
    }
}
