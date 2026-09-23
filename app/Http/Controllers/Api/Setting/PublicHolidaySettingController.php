<?php

namespace App\Http\Controllers\Api\Setting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\StorePublicHolidayRequest;
use App\Http\Requests\Setting\UpdatePublicHolidayRequest;
use App\Http\Resources\Setting\PublicHolidayResource;
use App\Models\PublicHoliday;
use App\Services\Setting\PublicHolidayService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('Settings', weight: 10)]
class PublicHolidaySettingController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private PublicHolidayService $publicHolidays) {}

    /**
     * List public holidays for a month.
     */
    #[Endpoint(title: 'List public holidays', description: 'Returns public holidays for the given MM-YYYY month ordered by date. Defaults to the current month; invalid values fall back to it.')]
    #[QueryParameter('state', description: 'Month filter in MM-YYYY format. Defaults to the current month; invalid values fall back to it.', type: 'string')]
    public function index(Request $request): JsonResponse
    {
        $state = $request->query('state');

        $holidays = $this->publicHolidays->list(is_string($state) ? $state : null);

        return $this->successResponse(
            data: PublicHolidayResource::collection($holidays),
            message: 'Public holidays retrieved successfully.'
        );
    }

    /**
     * Create a public holiday.
     */
    #[Endpoint(title: 'Create public holiday', description: 'Creates a single public holiday. Each date may only be used once.')]
    public function store(StorePublicHolidayRequest $request): JsonResponse
    {
        $publicHoliday = $this->publicHolidays->create($request->validated());

        return $this->successResponse(
            data: PublicHolidayResource::make($publicHoliday),
            message: 'Public holiday created successfully.',
            code: Response::HTTP_CREATED
        );
    }

    /**
     * Update a public holiday.
     */
    #[Endpoint(title: 'Update public holiday', description: 'Updates a single public holiday by id. Each date may only be used once.')]
    public function update(UpdatePublicHolidayRequest $request, PublicHoliday $publicHoliday): JsonResponse
    {
        $publicHoliday = $this->publicHolidays->update($publicHoliday, $request->validated());

        return $this->successResponse(
            data: PublicHolidayResource::make($publicHoliday),
            message: 'Public holiday updated successfully.'
        );
    }

    /**
     * Delete a public holiday.
     */
    #[Endpoint(title: 'Delete public holiday', description: 'Deletes a single public holiday by id.')]
    public function destroy(PublicHoliday $publicHoliday): JsonResponse
    {
        $this->publicHolidays->delete($publicHoliday);

        return $this->successResponse(message: 'Public holiday deleted successfully.');
    }
}
