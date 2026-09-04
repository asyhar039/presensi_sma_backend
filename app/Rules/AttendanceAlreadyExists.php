<?php

namespace App\Rules;

use Closure;
use App\Models\Attendance;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class AttendanceAlreadyExists implements ValidationRule
{
     public function __construct(
        protected int $scheduleId,
        protected string $attendanceDate
    ) {
    }
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = Attendance::where('schedule_id', $this->scheduleId)
            ->where('student_id', $value)
            ->where('attendance_date', $this->attendanceDate)
            ->exists();

        if ($exists) {
            $fail('Siswa sudah melakukan presensi pada jadwal ini');
        }
    }
}
