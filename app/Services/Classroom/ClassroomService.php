<?php

namespace App\Services\Classroom;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClassroomService
{
    /**
     * @return LengthAwarePaginator<int, Classroom>
     */
    public function paginate(int $perPage = 15, ?int $academicYearId = null): LengthAwarePaginator
    {
        return Classroom::query()
            ->with(['academicYear', 'homeroomTeacher.user'])
            ->withCount('students')
            ->when($academicYearId, fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Classroom
    {
        return DB::transaction(function () use ($data): Classroom {
            $academicYearId = $this->resolveAcademicYearId($data['academic_year_id'] ?? null);
            $homeroomTeacherId = $data['homeroom_teacher_id'] ?? null;

            if ($homeroomTeacherId !== null) {
                $this->ensureHomeroomAvailable($homeroomTeacherId, $academicYearId);
            }

            $classroom = Classroom::query()->create([
                'name' => $data['name'],
                'academic_year_id' => $academicYearId,
                'homeroom_teacher_id' => $homeroomTeacherId,
            ]);

            return $classroom->load(['academicYear', 'homeroomTeacher.user']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Classroom $classroom, array $data): Classroom
    {
        return DB::transaction(function () use ($classroom, $data): Classroom {
            $academicYearId = $data['academic_year_id'] ?? $classroom->academic_year_id;

            if (array_key_exists('homeroom_teacher_id', $data) && $data['homeroom_teacher_id'] !== null) {
                $this->ensureHomeroomAvailable($data['homeroom_teacher_id'], $academicYearId, $classroom->id);
            }

            $classroom->fill($data);
            $classroom->save();

            return $classroom->load(['academicYear', 'homeroomTeacher.user']);
        });
    }

    public function delete(Classroom $classroom): void
    {
        $classroom->delete();
    }

    public function assignHomeroom(Classroom $classroom, ?int $teacherId): Classroom
    {
        return DB::transaction(function () use ($classroom, $teacherId): Classroom {
            if ($teacherId !== null) {
                $this->ensureHomeroomAvailable($teacherId, $classroom->academic_year_id, $classroom->id);
            }

            $classroom->homeroom_teacher_id = $teacherId;
            $classroom->save();

            return $classroom->load(['academicYear', 'homeroomTeacher.user']);
        });
    }

    /**
     * @param  list<int>  $studentIds
     */
    public function syncStudents(Classroom $classroom, array $studentIds): Classroom
    {
        return DB::transaction(function () use ($classroom, $studentIds): Classroom {
            $classroom->students()->sync($studentIds);

            return $classroom->load(['academicYear', 'homeroomTeacher.user', 'students.user']);
        });
    }

    public function removeStudent(Classroom $classroom, Student $student): void
    {
        $classroom->students()->detach($student->id);
    }

    /**
     * @return LengthAwarePaginator<int, Student>
     */
    public function paginateStudents(Classroom $classroom, int $perPage = 15): LengthAwarePaginator
    {
        return $classroom->students()->with('user')->paginate($perPage);
    }

    private function resolveAcademicYearId(?int $academicYearId): int
    {
        if ($academicYearId !== null) {
            return $academicYearId;
        }

        $activeId = AcademicYear::query()->active()->value('id');

        if ($activeId === null) {
            throw ValidationException::withMessages([
                'academic_year_id' => 'No active academic year exists. Provide academic_year_id explicitly.',
            ]);
        }

        return $activeId;
    }

    private function ensureHomeroomAvailable(int $teacherId, int $academicYearId, ?int $exceptClassroomId = null): void
    {
        $exists = Classroom::query()
            ->where('homeroom_teacher_id', $teacherId)
            ->where('academic_year_id', $academicYearId)
            ->when($exceptClassroomId, fn ($query) => $query->whereKeyNot($exceptClassroomId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'homeroom_teacher_id' => 'This teacher is already a homeroom teacher in the selected academic year.',
            ]);
        }
    }
}
