<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\PermissionRequest;
use Carbon\Carbon;

class GenerateAlpaAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:generate-alpa';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate alpa untuk siswa yang tidak hadir';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        $dayMap = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
        ];

        $todayDay = $dayMap[$now->englishDayOfWeek];

        $schedules = Schedule::where('day', $todayDay)
            ->where('is_active', true)
            ->whereTime('end_time', '<', $now->format('H:i:s'))
            ->get();
        
        foreach ($schedules as $schedule) {
            /** @var Schedule $schedule */

            $students = $schedule->schoolClass->students()
                ->whereHas('user', function ($query) {
                    $query->where('is_active', true);
                })
                ->get();

            foreach ($students as $student) {
                /** @var Student $student */

                $attendanceExists = Attendance::where('schedule_id', $schedule->id)
                    ->where('student_id', $student->id)
                    ->whereDate('attendance_date', $now->toDateString())
                    ->exists();

                if ($attendanceExists) {
                    continue;
                }

                $hasApprovedPermission = PermissionRequest::where('student_id', $student->id)
                    ->where('status', 'approved')
                    ->whereDate('start_date', '<=', $now->toDateString())
                    ->whereDate('end_date', '>=', $now->toDateString())
                    ->exists();
                
                if ($hasApprovedPermission) {
                    continue;
                }

                Attendance::create([
                    'schedule_id' => $schedule->id,
                    'student_id' => $student->id,
                    'attendance_date' => $now->toDateString(),
                    'check_in' => null,
                    'status' => 'alpa',
                ]);
            }
        }
    }
}
