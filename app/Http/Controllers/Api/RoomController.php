<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
    $rooms = Room::all();

    return RoomResource::collection($rooms);
    }

    public function store(StoreRoomRequest $request)
    {
        $room = Room::create([
            'name' =>$request->name,
        ]);

        return response()->json([
            'message' => 'Ruangan Berhasil dibuat',
            'data' => new RoomResource($room),
        ],201);
    }

    public function show(Room $room)
    {
        return new RoomResource($room);
    }

    public function update(UpdateRoomRequest $request, Room $room)
    {
        $room->update([
            'name' => $request->name,
        ]);
        return new RoomResource($room->fresh());
    }

    public function deactivate(Room $room)
    {
        $room->update([
            'is_active' => false,
        ]);

        return new RoomResource($room->fresh());
    }

    public function activate(Room $room)
    {
        $room->update([
            'is_active' => true,
        ]);
        return new RoomResource($room->fresh());
    }
}
