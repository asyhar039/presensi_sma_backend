<?php

namespace App\Services\Room;

use App\Models\Room;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RoomService
{
    /**
     * @return LengthAwarePaginator<int, Room>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Room::query()->latest()->paginate($perPage);
    }

    /**
     * @param  array{name: string}  $data
     */
    public function create(array $data): Room
    {
        return Room::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Room $room, array $data): Room
    {
        $room->fill($data);
        $room->save();

        return $room->refresh();
    }

    public function delete(Room $room): void
    {
        $room->delete();
    }
}
