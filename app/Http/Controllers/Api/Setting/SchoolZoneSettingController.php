<?php

namespace App\Http\Controllers\Api\Setting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\StoreSchoolZoneRequest;
use App\Http\Requests\Setting\UpdateSchoolZoneActiveRequest;
use App\Http\Requests\Setting\UpdateSchoolZoneRequest;
use App\Http\Resources\Setting\SchoolZoneResource;
use App\Models\SchoolZone;
use App\Services\Setting\SchoolZoneService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('Settings', weight: 10)]
class SchoolZoneSettingController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private SchoolZoneService $schoolZones) {}

    /**
     * List all school zones.
     */
    #[Endpoint(title: 'List school zones', description: 'Returns every school zone without pagination. Pass is_active to filter by status.')]
    #[QueryParameter('is_active', description: 'Filter by status: 1 for active, 0 for inactive. Omit to return all.', type: 'bool')]
    public function index(Request $request): JsonResponse
    {
        $filter = $request->query('is_active');

        $isActive = match (true) {
            $filter === '1' || $filter === 1 || $filter === true || $filter === 'true' => true,
            $filter === '0' || $filter === 0 || $filter === false || $filter === 'false' => false,
            default => null,
        };

        return $this->successResponse(
            data: SchoolZoneResource::collection($this->schoolZones->list($isActive)),
            message: 'School zones retrieved successfully.'
        );
    }

    /**
     * Create a school zone.
     */
    #[Endpoint(title: 'Create school zone', description: 'Creates a single school zone with a unique name (max 32 characters), at least 3 polygon points, and an optional is_active flag (defaults to active).')]
    public function store(StoreSchoolZoneRequest $request): JsonResponse
    {
        $schoolZone = $this->schoolZones->create($request->validated());

        return $this->successResponse(
            data: SchoolZoneResource::make($schoolZone),
            message: 'School zone created successfully.',
            code: Response::HTTP_CREATED
        );
    }

    /**
     * Show a single school zone.
     */
    #[Endpoint(title: 'Show school zone', description: 'Returns a single school zone by id.')]
    public function show(SchoolZone $schoolZone): JsonResponse
    {
        return $this->successResponse(
            data: SchoolZoneResource::make($schoolZone),
            message: 'School zone retrieved successfully.'
        );
    }

    /**
     * Update a school zone.
     */
    #[Endpoint(title: 'Update school zone', description: 'Updates a single school zone by id, keeping the name unique. Accepts PUT, PATCH, or POST.')]
    public function update(UpdateSchoolZoneRequest $request, SchoolZone $schoolZone): JsonResponse
    {
        $schoolZone = $this->schoolZones->update($schoolZone, $request->validated());

        return $this->successResponse(
            data: SchoolZoneResource::make($schoolZone),
            message: 'School zone updated successfully.'
        );
    }

    /**
     * Update a school zone status.
     */
    #[Endpoint(title: 'Update school zone status', description: 'Activates or deactivates a single school zone by id.')]
    public function updateActive(UpdateSchoolZoneActiveRequest $request, SchoolZone $schoolZone): JsonResponse
    {
        $schoolZone = $this->schoolZones->setActive($schoolZone, $request->validated());

        return $this->successResponse(
            data: SchoolZoneResource::make($schoolZone),
            message: 'School zone status updated successfully.'
        );
    }
}
