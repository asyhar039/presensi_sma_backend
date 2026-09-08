<?php

namespace App\Services\Room;

use App\Models\Room;
use App\Services\DataTable\DataTableBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class RoomService
{
    /**
     * List rooms searchable by name.
     *
     * @return LengthAwarePaginator<int, Room>
     */
    public function paginate(Request $request): LengthAwarePaginator
    {
        return DataTableBuilder::make(Room::query(), $request)
            ->searchable(['name'])
            ->sortable([
                'id' => 'id',
                'name' => 'name',
                'created_at' => 'created_at',
            ])
            ->paginate();
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
