<?php

namespace Database\Seeders;

use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $records = collect($this->rooms())->map(fn ($val) => [
            'name' => $val,
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        Room::insert($records);
    }

    /**
     * @return list<array{string}>
     */
    public function rooms(): array
    {
        return [
            'X E 1',
            'X E 2',
            'X E 3',
            'X E 4',
            'X E 5',
            'X E 6',
            'X E 7',
            'X E 8',
            'X E 9',
            'XI F 1',
            'XI F 2',
            'XI F 3',
            'XI F 4',
            'XI F 5',
            'XI F 6',
            'XI F 7',
            'XI F 8',
            'XI F 9',
            'XII F 1',
            'XII F 2',
            'XII F 3',
            'XII F 4',
            'XII F 5',
            'XII F 6',
            'XII F 7',
            'XII F 8',
            'XII F 9',
        ];
    }
}
