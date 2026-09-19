<?php

namespace App\Http\Controllers\Api\Setting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\UpdatePublicHolidaysRequest;
use App\Http\Resources\Setting\PublicHolidayResource;
use App\Services\Setting\PublicHolidaySettingService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Settings', weight: 10)]
class PublicHolidaySettingController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private PublicHolidaySettingService $publicHolidays) {}

    /**
     * List all public holidays.
     */
    #[Endpoint(title: 'List public holidays', description: 'Returns every public holiday ordered by date, without pagination.')]
    public function index(): JsonResponse
    {
        return $this->successResponse(
            data: PublicHolidayResource::collection($this->publicHolidays->all()),
            message: 'Public holidays retrieved successfully.'
        );
    }

    /**
     * Replace all public holidays.
     */
    #[Endpoint(title: 'Replace public holidays', description: 'Replaces the whole public holiday list. Accepts a JSON array of name and date pairs; dates and names must be unique.')]
    public function update(UpdatePublicHolidaysRequest $request): JsonResponse
    {
        $holidays = $this->publicHolidays->replace($request->validated());

        return $this->successResponse(
            data: PublicHolidayResource::collection($holidays),
            message: 'Public holidays updated successfully.'
        );
    }

    /**
     * Delete a public holiday by date.
     */
    #[Endpoint(title: 'Delete public holiday', description: 'Removes the public holiday matching the given YYYY-MM-DD date.')]
    public function destroy(string $date): JsonResponse
    {
        if (! $this->publicHolidays->removeByDate($date)) {
            return $this->errorResponse(
                message: 'Public holiday not found.',
                code: Response::HTTP_NOT_FOUND
            );
        }

        return $this->successResponse(message: 'Public holiday deleted successfully.');
    }
}
