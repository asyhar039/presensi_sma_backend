<?php

namespace Database\Seeders;

use App\Enums\Enums\SubjectEnums;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = collect(SubjectEnums::cases())
            ->map(fn ($subject) => [
                'id' => $subject->value,
                'name' => $subject->label(),
            ])
            ->all();

        Subject::upsert(
            $data,
            ['id'],
            ['name']
        );
    }
}
