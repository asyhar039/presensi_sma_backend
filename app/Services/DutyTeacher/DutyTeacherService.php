<?php

namespace App\Services\DutyTeacher;

use App\Models\AcademicYear;
use App\Models\DutyTeacher;
use App\Services\DataTable\DataTableBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DutyTeacherService
{
    /**
     * Paginated duty teachers. Defaults to the active academic year when no filter is given.
     *
     * @return LengthAwarePaginator<int, DutyTeacher>
     */
    public function paginate(Request $request): LengthAwarePaginator
    {
        if (! $request->filled('academic_year_id')) {
            $activeId = AcademicYear::query()->active()->value('id');
            if ($activeId !== null) {
                $request->merge(['academic_year_id' => $activeId]);
            }
        }

        return DataTableBuilder::make(DutyTeacher::query(), $request)
            ->with(['teacher.user', 'academicYear'])
            ->disableSearch()
            ->addFilter('search', ['nullable', 'string', 'max:128'], function (Builder $query, string $value): void {
                $query->whereHas('teacher.user', fn (Builder $userQuery) => $userQuery->where('name', 'LIKE', "%{$value}%"));
            })
            ->sortable([
                'id' => 'id',
                'day' => 'day',
                'start_time' => 'start_time',
                'created_at' => 'created_at',
            ])
            ->addFilter('academic_year_id', ['nullable', 'integer', 'exists:academic_years,id'], function (Builder $query, int $value): void {
                $query->where('academic_year_id', $value);
            })
            ->addFilter('teacher_id', ['nullable', 'integer', 'exists:teachers,id'], function (Builder $query, int $value): void {
                $query->where('teacher_id', $value);
            })
            ->addFilter('day', ['nullable', 'string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'], function (Builder $query, string $value): void {
                $query->where('day', $value);
            })
            ->paginate();
    }

    /**
     * @param  array{academic_year_id: int, teacher_id: int, day: string, start_time: string, end_time: string}  $data
     */
    public function create(array $data): void
    {
        $this->assertAvailable($data['academic_year_id'], $data['teacher_id'], $data['day'], $data['start_time'], $data['end_time']);

        DutyTeacher::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(DutyTeacher $dutyTeacher, array $data): void
    {
        $merged = array_merge($dutyTeacher->only(['academic_year_id', 'teacher_id', 'start_time', 'end_time']), $data);
        $merged['day'] = isset($data['day']) ? $data['day'] : $dutyTeacher->day->value;

        $this->assertAvailable(
            (int) $merged['academic_year_id'],
            (int) $merged['teacher_id'],
            (string) $merged['day'],
            substr((string) $merged['start_time'], 0, 5),
            substr((string) $merged['end_time'], 0, 5),
            $dutyTeacher->id
        );

        $dutyTeacher->fill($data);
        $dutyTeacher->save();
    }

    public function delete(DutyTeacher $dutyTeacher): void
    {
        $dutyTeacher->delete();
    }

    private function assertAvailable(int $academicYearId, int $teacherId, string $day, string $start, string $end, ?int $exceptId = null): void
    {
        $busy = DutyTeacher::query()
            ->where('academic_year_id', $academicYearId)
            ->where('teacher_id', $teacherId)
            ->where('day', $day)
            ->where('start_time', '<', $this->toTime($end))
            ->where('end_time', '>', $this->toTime($start))
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();

        if ($busy) {
            throw ValidationException::withMessages(['teacher_id' => 'The teacher already has a duty schedule overlapping that time.']);
        }
    }

    private function toTime(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }
}
