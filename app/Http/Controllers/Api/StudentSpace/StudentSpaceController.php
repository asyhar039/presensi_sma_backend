<?php

namespace App\Http\Controllers\Api\StudentSpace;

use App\Enums\LeaveRequestTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StudentSpace\StoreEarlyOutRequest;
use App\Http\Requests\StudentSpace\StoreLateArrivalRequest;
use App\Http\Requests\StudentSpace\StoreSickLeaveRequest;
use App\Http\Resources\StudentSpace\LeaveRequestListResource;
use App\Http\Resources\StudentSpace\LeaveRequestResource;
use App\Services\StudentSpace\LeaveRequestService;
use App\Services\StudentSpace\StudentSpaceService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Student Space', weight: 9)]
class StudentSpaceController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private StudentSpaceService $space,
        private LeaveRequestService $leaves,
    ) {}

    #[Endpoint(title: 'Get student information', description: 'Returns the active academic year and the student class in it.')]
    public function information(Request $request): JsonResponse
    {
        $year = $this->space->activeYear();
        $classroom = $this->space->resolveClassroom($request->user(), $year);

        if ($year === null || $classroom === null) {
            return $this->successResponse(
                data: ['academic_year' => null, 'class' => null],
                message: 'Student information retrieved successfully.'
            );
        }

        return $this->successResponse(
            data: [
                'academic_year' => [
                    'id' => $year->id,
                    'odd_start_date' => $year->odd_start_date->format('Y-m-d'),
                    'odd_end_date' => $year->odd_end_date->format('Y-m-d'),
                    'even_start_date' => $year->even_start_date->format('Y-m-d'),
                    'even_end_date' => $year->even_end_date->format('Y-m-d'),
                ],
                'class' => [
                    'id' => $classroom->id,
                    'name' => $classroom->name,
                    'homeroom_teacher' => $classroom->homeroomTeacher?->user ? [
                        'id' => $classroom->homeroomTeacher->id,
                        'name' => $classroom->homeroomTeacher->user->name,
                        'email' => $classroom->homeroomTeacher->user->email,
                    ] : null,
                ],
            ],
            message: 'Student information retrieved successfully.'
        );
    }

    #[Endpoint(title: 'Get presence information', description: 'Returns weekly class times, upcoming public holidays, and today leave-request flags.')]
    public function presence(Request $request): JsonResponse
    {
        $user = $request->user();
        $classroom = $this->space->resolveClassroom($user);

        $holidays = $this->space->upcomingHolidays()->map(fn ($holiday): array => [
            'name' => $holiday->name,
            'date' => $holiday->date->format('Y-m-d'),
        ])->all();

        return $this->successResponse(
            data: [
                'time' => $this->space->dailyTimes($classroom),
                'public_holidays' => $holidays,
                'has_early_out' => $this->leaves->hasTodayRequest($user, LeaveRequestTypeEnum::EarlyOut),
                'has_late_arrival' => $this->leaves->hasTodayRequest($user, LeaveRequestTypeEnum::LateArrival),
            ],
            message: 'Presence information retrieved successfully.'
        );
    }

    #[Endpoint(title: 'List my leave requests', description: 'Returns paginated leave requests of the authenticated student. Same DataTable contract as other indexes.')]
    #[QueryParameter('page', description: 'Current page number.', type: 'int', default: 1)]
    #[QueryParameter('per_page', description: 'Items per page (max 50).', type: 'int', default: 10)]
    #[QueryParameter('search', description: 'Search by request key.', type: 'string')]
    #[QueryParameter('type', description: 'Filter by type: sick_leave, early_out, late_arrival.', type: 'string')]
    #[QueryParameter('status', description: 'Filter by status: pending, approved, rejected.', type: 'string')]
    #[QueryParameter('sortBy', description: 'Sort column: id, requested_at, date.', type: 'string')]
    #[QueryParameter('order', description: 'Sort direction: asc or desc.', type: 'string')]
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->leaves->paginate($request, $request->user());

        return $this->paginatedResponse(
            $paginator,
            LeaveRequestListResource::collection($paginator->items()),
            'Leave requests retrieved successfully.'
        );
    }

    #[Endpoint(title: 'Get leave request detail', description: 'Returns one leave request owned by the authenticated student.')]
    public function show(Request $request, int $leaveRequest): JsonResponse
    {
        $leave = $this->leaves->findOwned($request->user(), $leaveRequest)->loadMissing('approvals');

        return $this->successResponse(
            data: LeaveRequestResource::make($leave),
            message: 'Leave request retrieved successfully.'
        );
    }

    #[Endpoint(title: 'Create sick leave request', description: 'Creates a sick_leave request; range_date is computed from school days.')]
    public function storeSickLeave(StoreSickLeaveRequest $request): JsonResponse
    {
        $leave = $this->leaves->createSickLeave($request->user(), $request->validated(), $request->attachment());

        return $this->successResponse(
            data: LeaveRequestResource::make($leave),
            message: 'Leave request created successfully.',
            code: 201
        );
    }

    #[Endpoint(title: 'Create early out request', description: 'Creates an early_out request for today (one per day, school days only).')]
    public function storeEarlyOut(StoreEarlyOutRequest $request): JsonResponse
    {
        $leave = $this->leaves->createEarlyOut($request->user(), $request->validated(), $request->attachment());

        return $this->successResponse(
            data: LeaveRequestResource::make($leave),
            message: 'Leave request created successfully.',
            code: 201
        );
    }

    #[Endpoint(title: 'Create late arrival request', description: 'Creates a late_arrival request for today (one per day, school days only).')]
    public function storeLateArrival(StoreLateArrivalRequest $request): JsonResponse
    {
        $leave = $this->leaves->createLateArrival($request->user(), $request->validated(), $request->attachment());

        return $this->successResponse(
            data: LeaveRequestResource::make($leave),
            message: 'Leave request created successfully.',
            code: 201
        );
    }
}
