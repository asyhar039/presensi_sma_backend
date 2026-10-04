<?php

namespace App\Services\AcademicYear;

use App\Models\AcademicYear;
use App\Services\DataTable\DataTableBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademicYearService
{
    /**
     * List academic years. Search is disabled; filter by semester or year.
     *
     * @return LengthAwarePaginator<int, AcademicYear>
     */
    public function paginate(Request $request): LengthAwarePaginator
    {
        return DataTableBuilder::make(AcademicYear::query(), $request)
            ->sortable([
                'id' => 'id',
                'odd_start_date' => 'odd_start_date',
                'odd_end_date' => 'odd_end_date',
                'even_start_date' => 'even_start_date',
                'even_end_date' => 'even_end_date',
                'semester' => 'semester',
                'is_active' => 'is_active',
                'created_at' => 'created_at',
            ])
            ->addFilter('year', ['nullable', 'integer', 'min:1900', 'max:2100'], function (Builder $query, int $value): void {
                $query->where(function (Builder $query) use ($value): void {
                    $query->whereYear('odd_start_date', $value)->orWhereYear('odd_end_date', $value)->orWhereYear('even_start_date', $value)->orWhereYear('even_end_date', $value);
                });
            })
            ->paginate();
    }

    /**
     * @param  array{odd_start_date: string, odd_end_date: string, even_start_date: string, even_end_date: string, is_active?: bool}  $data
     */
    public function create(array $data): AcademicYear
    {
        return DB::transaction(function () use ($data): AcademicYear {
            if (($data['is_active'] ?? false) === true) {
                AcademicYear::query()->where('is_active', true)->update(['is_active' => false]);
            }

            return AcademicYear::query()->create($data);
        });
    }

    /**
     * @param  array{odd_start_date: string, odd_end_date: string, even_start_date: string, even_end_date: string, is_active?: bool}  $data
     */
    public function update(AcademicYear $academicYear, array $data): AcademicYear
    {
        return DB::transaction(function () use ($academicYear, $data): AcademicYear {
            if (($data['is_active'] ?? null) === true) {
                AcademicYear::query()->whereKeyNot($academicYear->getKey())->where('is_active', true)->update(['is_active' => false]);
            }

            $academicYear->fill($data);
            $academicYear->save();

            return $academicYear->refresh();
        });
    }

    public function delete(AcademicYear $academicYear): void
    {
        $academicYear->delete();
    }
}
