<?php

namespace App\Services\AcademicYear;

use App\Models\AcademicYear;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AcademicYearService
{
    /**
     * @return LengthAwarePaginator<int, AcademicYear>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return AcademicYear::query()->latest()->paginate($perPage);
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
