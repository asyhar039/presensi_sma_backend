<?php

namespace App\Services\Homeroom;

use App\Enums\GenderEnums;
use App\Enums\StudentStatusEnums;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Services\DataTable\DataTableBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class HomeroomService
{
    public function resolveClassroom(User $user): ?Classroom
    {
        $teacherId = $user->teacher?->id;

        if ($teacherId === null) {
            return null;
        }

        $activeYearId = AcademicYear::query()->active()->value('id');

        if ($activeYearId === null) {
            return null;
        }

        return Classroom::query()
            ->where('academic_year_id', $activeYearId)
            ->where('homeroom_teacher_id', $teacherId)
            ->first();
    }

    public function findStudent(Classroom $classroom, int $studentId): ?Student
    {
        return Student::query()
            ->whereKey($studentId)
            ->whereHas('classrooms', fn (Builder $query): Builder => $query->whereKey($classroom->id))
            ->with('user')
            ->first();
    }

    /**
     * Same request/response contract as GET /students, scoped to the homeroom class.
     *
     * @return LengthAwarePaginator<int, Student>
     */
    public function paginateStudents(Request $request, Classroom $classroom): LengthAwarePaginator
    {
        $statuses = implode(',', array_column(StudentStatusEnums::cases(), 'value'));
        $genders = implode(',', array_column(GenderEnums::cases(), 'value'));

        return DataTableBuilder::make(Student::query()->whereHas('classrooms', function (Builder $query) use ($classroom): void {
            $query->whereKey($classroom->id);
        }), $request)
            ->with('user')
            ->searchable(['user.name', 'user.identity_number'])
            ->sortable([
                'id' => 'id',
                'name' => function (Builder $query, string $direction): void {
                    $query->orderBy(User::select('name')->whereColumn('users.id', 'students.user_id'), $direction);
                },
                'created_at' => 'created_at',
            ])
            ->addFilter('status', ['nullable', 'string', 'in:'.$statuses], function (Builder $query, string $value): void {
                $query->where('status', $value);
            })
            ->addFilter('gender', ['nullable', 'string', 'in:'.$genders], function (Builder $query, string $value): void {
                $query->where('gender', $value);
            })
            ->paginate();
    }
}
