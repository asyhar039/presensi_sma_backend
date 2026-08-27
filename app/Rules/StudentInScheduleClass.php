<?php

namespace App\Rules;

use Closure;
use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class StudentInScheduleClass implements ValidationRule
{
    public function __construct(
        protected int $scheduleId
    ) {
    }
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $student = Student::find($value);
        $schedule = Schedule::find($this->scheduleId);

        if (!$student || !$schedule) {
            return;
        }

        if ($student->class_id != $schedule->class_id) {
            $fail('Siswa tidak terdaftar pada kelas jadwal tersebut');
        }
    }
}
