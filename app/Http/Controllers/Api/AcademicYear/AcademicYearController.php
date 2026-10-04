<?php

namespace App\Http\Controllers\Api\AcademicYear;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicYear\StoreAcademicYearRequest;
use App\Http\Requests\AcademicYear\UpdateAcademicYearRequest;
use App\Http\Resources\AcademicYearResource;
use App\Http\Resources\Dropdown\AcademicYearDropdownResource;
use App\Models\AcademicYear;
use App\Services\AcademicYear\AcademicYearService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('Academic Years', weight: 2)]
class AcademicYearController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private AcademicYearService $academicYearService) {}

    /**
     * List academic years with pagination.
     */
    #[Endpoint(title: 'List academic years', description: 'Returns paginated academic years. Filter by semester or year; sort by allowed columns.')]
    #[QueryParameter('page', description: 'Current page number.', type: 'int', default: 1)]
    #[QueryParameter('per_page', description: 'Items per page (max 50).', type: 'int', default: 10)]
    #[QueryParameter('semester', description: 'Filter by semester: odd, even.', type: 'string')]
    #[QueryParameter('year', description: 'Filter by year matching the start or end date.', type: 'int')]
    #[QueryParameter('sortBy', description: 'Sort column: id, odd_start_date, odd_end_date, even_start_date, even_end_date, semester, is_active, created_at.', type: 'string')]
    #[QueryParameter('order', description: 'Sort direction: asc or desc.', type: 'string')]
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->academicYearService->paginate($request);

        return $this->paginatedResponse(
            $paginator,
            AcademicYearResource::collection($paginator->items()),
            'Academic years retrieved successfully.'
        );
    }

    /**
     * Academic year options for dropdowns.
     */
    #[Endpoint(title: 'List academic years dropdown', description: 'Returns paginated {value, label} academic year options; label is the year range e.g. 2025/2026.')]
    #[QueryParameter('search', description: 'Search by year, e.g. 2025.', type: 'string')]
    #[QueryParameter('page', description: 'Current page number.', type: 'int', default: 1)]
    #[QueryParameter('per_page', description: 'Items per page (max 50).', type: 'int', default: 20)]
    public function dropdown(Request $request): JsonResponse
    {
        $paginator = $this->academicYearService->dropdown($request);

        return $this->paginatedResponse(
            $paginator,
            AcademicYearDropdownResource::collection($paginator->items()),
            'Academic years dropdown retrieved successfully.'
        );
    }

    /**
     * Create a new academic year. Only one academic year can be active.
     */
    #[Endpoint(title: 'Create academic year', description: 'Creates an academic year; when is_active is true all other years are deactivated.')]
    public function store(StoreAcademicYearRequest $request): JsonResponse
    {
        $academicYear = $this->academicYearService->create($request->academicYearData());

        return $this->successResponse(
            data: AcademicYearResource::make($academicYear),
            message: 'Academic year created successfully.',
            code: Response::HTTP_CREATED
        );
    }

    /**
     * Show a single academic year.
     */
    #[Endpoint(title: 'Show academic year', description: 'Returns a single academic year by id.')]
    public function show(AcademicYear $academicYear): JsonResponse
    {
        return $this->successResponse(
            data: AcademicYearResource::make($academicYear),
            message: 'Academic year retrieved successfully.'
        );
    }

    /**
     * Update an academic year. Setting is_active true deactivates others.
     */
    #[Endpoint(title: 'Update academic year', description: 'Updates an academic year; when is_active is true all other years are deactivated.')]
    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicYear): JsonResponse
    {
        $academicYear = $this->academicYearService->update($academicYear, $request->academicYearData());

        return $this->successResponse(
            data: AcademicYearResource::make($academicYear),
            message: 'Academic year updated successfully.'
        );
    }

    /**
     * Delete an academic year.
     */
    #[Endpoint(title: 'Delete academic year', description: 'Deletes an academic year by id.')]
    public function destroy(AcademicYear $academicYear): JsonResponse
    {
        $this->academicYearService->delete($academicYear);

        return $this->successResponse(message: 'Academic year deleted successfully.');
    }
}
