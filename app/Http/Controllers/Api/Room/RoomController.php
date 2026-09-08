<?php

namespace App\Http\Controllers\Api\Room;

use App\Http\Controllers\Controller;
use App\Http\Requests\Room\StoreRoomRequest;
use App\Http\Requests\Room\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use App\Services\Room\RoomService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('Rooms', weight: 3)]
class RoomController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private RoomService $roomService) {}

    /**
     * List rooms with pagination.
     */
    #[Endpoint(title: 'List rooms', description: 'Returns paginated rooms searchable by name; sort by allowed columns.')]
    #[QueryParameter('page', description: 'Current page number.', type: 'int', default: 1)]
    #[QueryParameter('per_page', description: 'Items per page (max 50).', type: 'int', default: 10)]
    #[QueryParameter('search', description: 'Search by room name.', type: 'string')]
    #[QueryParameter('sortBy', description: 'Sort column: id, name, created_at.', type: 'string')]
    #[QueryParameter('order', description: 'Sort direction: asc or desc.', type: 'string')]
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->roomService->paginate($request);

        return $this->paginatedResponse(
            $paginator,
            RoomResource::collection($paginator->items()),
            'Rooms retrieved successfully.'
        );
    }

    /**
     * Create a new room.
     */
    #[Endpoint(title: 'Create room', description: 'Creates a room with a unique name.')]
    public function store(StoreRoomRequest $request): JsonResponse
    {
        $room = $this->roomService->create($request->validated());

        return $this->successResponse(
            data: RoomResource::make($room),
            message: 'Room created successfully.',
            code: Response::HTTP_CREATED
        );
    }

    /**
     * Show a single room.
     */
    #[Endpoint(title: 'Show room', description: 'Returns a single room by id.')]
    public function show(Room $room): JsonResponse
    {
        return $this->successResponse(
            data: RoomResource::make($room),
            message: 'Room retrieved successfully.'
        );
    }

    /**
     * Update a room.
     */
    #[Endpoint(title: 'Update room', description: 'Updates a room name keeping uniqueness.')]
    public function update(UpdateRoomRequest $request, Room $room): JsonResponse
    {
        $room = $this->roomService->update($room, $request->validated());

        return $this->successResponse(
            data: RoomResource::make($room),
            message: 'Room updated successfully.'
        );
    }

    /**
     * Delete a room.
     */
    #[Endpoint(title: 'Delete room', description: 'Deletes a room by id.')]
    public function destroy(Room $room): JsonResponse
    {
        $this->roomService->delete($room);

        return $this->successResponse(message: 'Room deleted successfully.');
    }
}
