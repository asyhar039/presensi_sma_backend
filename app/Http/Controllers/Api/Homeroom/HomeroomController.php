<?php

namespace App\Http\Controllers\Api\Homeroom;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Services\Homeroom\HomeroomService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('Homeroom', weight: 8)]
class HomeroomController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private HomeroomService $homeroomService) {}

    #[Endpoint(title: 'Get my homeroom class', description: 'Returns the authenticated teacher homeroom classroom in the active academic year.')]
    public function show(Request $request): JsonResponse
    {
        $classroom = $this->homeroomService->resolveClassroom($request->user());

        return $this->successResponse(
            data: [
                'has_homeroom' => $classroom !== null,
                'class' => $classroom ? ['id' => $classroom->id, 'name' => $classroom->name] : null,
            ],
            message: 'Homeroom retrieved successfully.'
        );
    }

    #[Endpoint(title: 'List my homeroom students', description: 'Returns paginated students of the authenticated teacher homeroom class. Same filters as GET /students.')]
    #[QueryParameter('page', description: 'Current page number.', type: 'int', default: 1)]
    #[QueryParameter('per_page', description: 'Items per page (max 50).', type: 'int', default: 10)]
    #[QueryParameter('search', description: 'Search by student name or identity number.', type: 'string')]
    #[QueryParameter('status', description: 'Filter by status: active, inactive, graduated, dropped_out.', type: 'string')]
    #[QueryParameter('gender', description: 'Filter by gender: male, female.', type: 'string')]
    #[QueryParameter('sortBy', description: 'Sort column: id, name, created_at.', type: 'string')]
    #[QueryParameter('order', description: 'Sort direction: asc or desc.', type: 'string')]
    public function students(Request $request): JsonResponse
    {
        $classroom = $this->homeroomService->resolveClassroom($request->user());

        if ($classroom === null) {
            abort(Response::HTTP_NOT_FOUND, 'You are not assigned as a homeroom teacher in the active academic year.');
        }

        $paginator = $this->homeroomService->paginateStudents($request, $classroom);

        return $this->paginatedResponse(
            $paginator,
            StudentResource::collection($paginator->items()),
            'Homeroom students retrieved successfully.'
        );
    }
}
