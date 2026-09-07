<?php

namespace App\Services\Subject;

use App\Models\Subject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SubjectService
{
    /**
     * @return LengthAwarePaginator<int, Subject>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Subject::query()->latest()->paginate($perPage);
    }

    /**
     * @param  array{name: string}  $data
     */
    public function create(array $data): Subject
    {
        return Subject::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Subject $subject, array $data): Subject
    {
        $subject->fill($data);
        $subject->save();

        return $subject->refresh();
    }

    public function delete(Subject $subject): void
    {
        $subject->delete();
    }
}
