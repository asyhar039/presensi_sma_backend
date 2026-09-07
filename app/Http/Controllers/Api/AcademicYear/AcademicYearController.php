<?php

namespace App\Http\Controllers\Api\AcademicYear;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicYear\StoreAcademicYearRequest;
use App\Http\Requests\AcademicYear\UpdateAcademicYearRequest;
use App\Http\Resources\AcademicYearResource;
use App\Models\AcademicYear;
use App\Services\AcademicYear\AcademicYearService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
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
    #[Endpoint(title: 'List academic years', description: 'Returns paginated academic years ordered by newest first.')]
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->academicYearService->paginate((int) $request->integer('per_page', 15));

        return $this->successResponse(
            data: AcademicYearResource::collection($paginator->items()),
            message: 'Academic years retrieved successfully.',
            code: Response::HTTP_OK,
            meta: $this->paginationMeta($paginator)
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
