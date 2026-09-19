<?php

namespace App\Http\Controllers\Api\Setting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\UpdateSchoolZonesRequest;
use App\Http\Resources\Setting\SchoolZoneResource;
use App\Services\Setting\SchoolZoneSettingService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Settings', weight: 10)]
class SchoolZoneSettingController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private SchoolZoneSettingService $schoolZones) {}

    /**
     * List all school zones.
     */
    #[Endpoint(title: 'List school zones', description: 'Returns every school zone polygon, without pagination.')]
    public function index(): JsonResponse
    {
        return $this->successResponse(
            data: SchoolZoneResource::collection($this->schoolZones->all()),
            message: 'School zones retrieved successfully.'
        );
    }

    /**
     * Replace all school zones.
     */
    #[Endpoint(title: 'Replace school zones', description: 'Replaces the whole school zone list. Accepts a JSON array of name and points pairs; each zone needs at least 3 latitude/longitude points.')]
    public function update(UpdateSchoolZonesRequest $request): JsonResponse
    {
        $zones = $this->schoolZones->replace($request->validated());

        return $this->successResponse(
            data: SchoolZoneResource::collection($zones),
            message: 'School zones updated successfully.'
        );
    }

    /**
     * Delete a school zone by name.
     */
    #[Endpoint(title: 'Delete school zone', description: 'Removes the school zone matching the given name.')]
    public function destroy(string $name): JsonResponse
    {
        if (! $this->schoolZones->removeByName($name)) {
            return $this->errorResponse(
                message: 'School zone not found.',
                code: Response::HTTP_NOT_FOUND
            );
        }

        return $this->successResponse(message: 'School zone deleted successfully.');
    }
}
