<?php

namespace App\Services\AcademicYear;

use App\Enums\Enums\SemesterEnums;
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
        $semesters = implode(',', array_column(SemesterEnums::cases(), 'value'));

        return DataTableBuilder::make(AcademicYear::query(), $request)
            ->sortable([
                'id' => 'id',
                'start_date' => 'start_date',
                'end_date' => 'end_date',
                'semester' => 'semester',
                'is_active' => 'is_active',
                'created_at' => 'created_at',
            ])
            ->addFilter('semester', ['nullable', 'string', 'in:'.$semesters], function (Builder $query, string $value): void {
                $query->where('semester', $value);
            })
            ->addFilter('year', ['nullable', 'integer', 'min:1900', 'max:2100'], function (Builder $query, int $value): void {
                $query->where(function (Builder $query) use ($value): void {
                    $query->whereYear('start_date', $value)->orWhereYear('end_date', $value);
                });
            })
            ->paginate();
    }

    /**
     * @param  array{start_date: string, end_date: string, semester: string, is_active?: bool}  $data
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
     * @param  array<string, mixed>  $data
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
