<?php

namespace App\Services\Subject;

use App\Models\Subject;
use App\Services\DataTable\DataTableBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class SubjectService
{
    /**
     * List subjects searchable by name.
     *
     * @return LengthAwarePaginator<int, Subject>
     */
    public function paginate(Request $request): LengthAwarePaginator
    {
        return DataTableBuilder::make(Subject::query(), $request)
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
