<?php

namespace App\Rules;

use Closure;
use App\Models\Schedule;
use Illuminate\Contracts\Validation\ValidationRule;
use Carbon\Carbon;

class AttendanceWithinSchedule implements ValidationRule
{
     public function __construct(
        protected int $scheduleId,
        protected string $attendanceDate,
        protected string $checkIn
    ) {
    }
    
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $schedule = Schedule::find($this->scheduleId);

        if(!$schedule) {
            return;
        }

        $dayMap = [
            'monday' => 'Senin',
            'tuesday' => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday' => 'Kamis',
            'friday' => 'Jumat',
            'saturday' => 'Sabtu',
            'sunday' => 'Minggu',
        ];

        $attendanceDay = $dayMap [
            strtolower(Carbon::parse($this->attendanceDate)->englishDayOfWeek)
        ];

        if($attendanceDay !== $schedule->day) {
            $fail('Tanggal presensi tidak sesuai dengan hari jadwal saat ini');
            return;
        }

        $checkIn = Carbon::createFromFormat('H:i', $this->checkIn);
        $startTime = Carbon::createFromFormat('H:i:s', $schedule->start_time);
        $endTime = Carbon::createFromFormat('H:i:s', $schedule->end_time);
        
        if ($checkIn->lt($startTime) || $checkIn->gt($endTime)) {
            $fail('Waktu presensi berada di luar jam jadwal.');
        }
    }
}
