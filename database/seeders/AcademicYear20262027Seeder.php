<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\DutyTeacher;
use App\Models\Student;
use App\Models\StudentClassroom;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use App\Models\User;
use App\Services\Setting\ScheduleSettingService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AcademicYear20262027Seeder extends Seeder
{
    protected string $file = 'database/data/academic-year-2026-2027.json';

    /**
     * @var Collection<string, Teacher>
     */
    protected Collection $teachersMap;

    /**
     * @var Collection<string, Classroom>
     */
    protected Collection $classroomsMap;

    protected AcademicYear $academicYear;

    public function run(): void
    {
        $data = json_decode(file_get_contents(base_path($this->file)), true);

        DB::transaction(function () use ($data): void {
            $this->academicYear = $this->createAcademicYear($data['academic_years']);

            $this->seedTeachers($data['teachers']);
            $this->seedTeacherSubjects($data['teachers']);

            $this->seedClassrooms($data['classrooms']);

            $this->seedStudents($data['students']);
            $this->seedDutyTeachers($data['duty_teachers']);
            $this->seedSchedules($data['schedules']);
        });
    }

    protected function createAcademicYear(array $data): AcademicYear
    {
        return AcademicYear::create([
            'odd_start_date' => $data['odd_start_date'],
            'odd_end_date' => $data['odd_end_date'],
            'even_start_date' => $data['even_start_date'],
            'even_end_date' => $data['even_end_date'],
            'is_active' => true,
        ]);
    }

    protected function seedTeachers(array $teachersData): void
    {
        $now = Carbon::now();
        $defaultPassword = Hash::make('password');

        $usersPayload = collect($teachersData)->map(fn (array $teacher) => [
            'name' => $teacher['name'],
            'email' => $teacher['email'],
            'password' => $defaultPassword,
            'role' => RoleEnum::Teacher->value,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        User::insert($usersPayload);

        $users = User::whereIn('email', collect($teachersData)->pluck('email'))
            ->get()
            ->keyBy('email');

        $teachersPayload = collect($teachersData)->map(fn (array $teacher) => [
            'user_id' => $users->get($teacher['email'])->id,
            'gender' => $teacher['gender'],
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        Teacher::insert($teachersPayload);

        $teacherModels = Teacher::whereIn('user_id', $users->pluck('id'))
            ->get()
            ->keyBy('user_id');

        $this->teachersMap = collect($teachersData)->mapWithKeys(function (array $teacher) use ($users, $teacherModels) {
            $userId = $users->get($teacher['email'])->id;
            return [$teacher['ref'] => $teacherModels->get($userId)];
        });
    }

    protected function seedTeacherSubjects(array $teachersData): void
    {
        $now = Carbon::now();

        $payload = collect($teachersData)->flatMap(function (array $teacher) use ($now) {
            $teacherModel = $this->teachersMap->get($teacher['ref']);

            return collect($teacher['subjects'])->map(fn (int $subjectId) => [
                'teacher_id' => $teacherModel->id,
                'subject_id' => $subjectId,
                'academic_year_id' => $this->academicYear->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        })->all();

        if (!empty($payload)) {
            TeacherSubject::insert($payload);
        }
    }

    protected function seedClassrooms(array $classroomsData): void
    {
        $now = Carbon::now();

        $payload = collect($classroomsData)->map(fn (array $classroom) => [
            'name' => $classroom['name'],
            'academic_year_id' => $this->academicYear->id,
            'homeroom_teacher_id' => isset($classroom['homeroom']) ? $this->teachersMap->get($classroom['homeroom'])?->id : null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        Classroom::insert($payload);

        $classroomModels = Classroom::where('academic_year_id', $this->academicYear->id)
            ->whereIn('name', collect($classroomsData)->pluck('name'))
            ->get()
            ->keyBy('name');

        $this->classroomsMap = collect($classroomsData)->mapWithKeys(fn (array $classroom) => [
            $classroom['ref'] => $classroomModels->get($classroom['name']),
        ]);
    }

    protected function seedStudents(array $studentsData): void
    {
        $now = Carbon::now();
        $defaultPassword = Hash::make('password');

        $usersPayload = collect($studentsData)->map(fn (array $student) => [
            'identity_number' => $student['identity_number'],
            'name' => $student['name'],
            'email' => $student['email'],
            'password' => $defaultPassword,
            'role' => RoleEnum::Student->value,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        User::insert($usersPayload);

        $users = User::whereIn('email', collect($studentsData)->pluck('email'))
            ->get()
            ->keyBy('email');

        $studentsPayload = collect($studentsData)->map(fn (array $student) => [
            'user_id' => $users->get($student['email'])->id,
            'gender' => $student['gender'],
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        Student::insert($studentsPayload);

        $studentModels = Student::whereIn('user_id', $users->pluck('id'))
            ->get()
            ->keyBy('user_id');

        $pivotPayload = collect($studentsData)->map(function (array $student) use ($users, $studentModels, $now) {
            $user = $users->get($student['email']);
            $studentModel = $studentModels->get($user->id);
            $classroom = $this->classroomsMap->get($student['class']);

            return [
                'student_id' => $studentModel->id,
                'classroom_id' => $classroom->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        StudentClassroom::insert($pivotPayload);
    }

    protected function seedDutyTeachers(array $dutyTeachersData): void
    {
        $now = Carbon::now();

        $payload = collect($dutyTeachersData)->flatMap(function (array $duty) use ($now) {
            return collect($duty['teachers'])->map(function (string $teacherRef) use ($duty, $now) {
                return [
                    'academic_year_id' => $this->academicYear->id,
                    'teacher_id' => $this->teachersMap->get($teacherRef)->id,
                    'day' => $duty['day'],
                    'start_time' => $duty['start'],
                    'end_time' => $duty['end'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            });
        })->all();

        if (!empty($payload)) {
            DutyTeacher::insert($payload);
        }
    }

    protected function seedSchedules(array $schedulesData): void
    {
        $service = app(ScheduleSettingService::class);

        $now = Carbon::now();
        $daysConfig = $service->all();
        $schedulesPayload = [];;

        foreach ($schedulesData as $daySchedule) {
            $day = strtolower($daySchedule['day']);
            $periodsConfig = collect($daysConfig[$day] ?? [])
                ->reject(fn (array $slot) => $slot['is_break'])
                ->values();

            foreach ($daySchedule['grid'] as $gridItem) {
                $classroom = $this->classroomsMap->get($gridItem['class']);

                foreach ($gridItem['subjects'] as $periodIndex => $teacherRef) {
                    if (empty($teacherRef)) {
                        continue;
                    }

                    $slot = $periodsConfig->get($periodIndex);
                    if (!$slot) {
                        continue;
                    }

                    $teacherRefs = Str::contains($teacherRef, '/')
                        ? explode('/', $teacherRef)
                        : [$teacherRef];

                    foreach ($teacherRefs as $ref) {
                        $teacher = $this->teachersMap->get(trim($ref));

                        if (!$teacher) {
                            continue;
                        }

                        $schedulesPayload[] = [
                            'classroom_id' => $classroom->id,
                            'teacher_id' => $teacher->id,
                            'day' => $day,
                            'period' => $periodIndex,
                            'start_time' => $slot['start'],
                            'end_time' => $slot['end'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }
        }

        if (!empty($schedulesPayload)) {
            ClassSchedule::insert($schedulesPayload);
        }
    }
}
