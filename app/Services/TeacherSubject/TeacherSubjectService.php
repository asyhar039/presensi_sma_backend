<?php

namespace App\Services\TeacherSubject;

use App\Models\AcademicYear;
use App\Models\TeacherSubject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class TeacherSubjectService
{
    /**
     * @return LengthAwarePaginator<int, TeacherSubject>
     */
    public function paginate(int $perPage = 15, ?int $teacherId = null, ?int $subjectId = null, ?int $academicYearId = null): LengthAwarePaginator
    {
        return TeacherSubject::query()
            ->with(['teacher.user', 'subject', 'academicYear'])
            ->when($teacherId, fn ($query) => $query->where('teacher_id', $teacherId))
            ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
            ->when($academicYearId, fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @param  array{teacher_id: int, subject_id: int, academic_year_id?: int}  $data
     */
    public function create(array $data): TeacherSubject
    {
        $academicYearId = $data['academic_year_id'] ?? AcademicYear::query()->active()->value('id');

        if ($academicYearId === null) {
            throw ValidationException::withMessages([
                'academic_year_id' => 'No active academic year exists. Provide academic_year_id explicitly.',
            ]);
        }

        $exists = TeacherSubject::query()
            ->where('teacher_id', $data['teacher_id'])
            ->where('subject_id', $data['subject_id'])
            ->where('academic_year_id', $academicYearId)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'subject_id' => 'This teacher is already assigned to the subject in the selected academic year.',
            ]);
        }

        $assignment = TeacherSubject::query()->create([
            'teacher_id' => $data['teacher_id'],
            'subject_id' => $data['subject_id'],
            'academic_year_id' => $academicYearId,
        ]);

        return $assignment->load(['teacher.user', 'subject', 'academicYear']);
    }

    public function delete(TeacherSubject $teacherSubject): void
    {
        $teacherSubject->delete();
    }
}
