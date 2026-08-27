<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\SchoolClass;
use App\Models\Room;
use App\Models\Mapel;
use App\Models\Schedule;
use App\Models\Attendance;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $today = Carbon::today();
        $now = Carbon::now();

        $totalStudents = Student::count();
        $totalTeachers = Teacher::count();
        $totalClasses = SchoolClass::count();
        $totalRooms = Room::count();
        $totalMapels = Mapel::count();

        $dayMap = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
        ];

        $todayDay = $dayMap[$today->englishDayOfWeek];

        $todaySchedules = Schedule::where('is_active', true)
            ->where('day', $todayDay)
            ->with('schoolClass.students')
            ->get();

        $expectedAttendances = $todaySchedules->sum(
            fn ($schedule) => $schedule->schoolClass->students->count()
        );

        $todayScheduleIds = $todaySchedules->pluck('id');

        $todayAttendances = Attendance::whereDate('attendance_date', $today)
            ->whereIn('schedule_id', $todayScheduleIds)
            ->count();

        $attendanceTodayPercentage = $expectedAttendances > 0
            ? round(($todayAttendances / $expectedAttendances) * 100, 1)
            : 0;

        $activeSubjectSessions = Schedule::where('is_active', true)
            ->where('day', $todayDay)
            ->whereTime('start_time', '<=', $now->format('H:i:s'))
            ->whereTime('end_time', '>=', $now->format('H:i:s'))
            ->count();

        $liveAttendances = Attendance::with([
            'student.user',
            'schedule.teacher.user',
            'schedule.mapel',
            'schedule.schoolClass',
        ])
            ->whereDate('attendance_date', $today)
            ->latest('check_in')
            ->take(10)
            ->get()
            ->map(function ($attendance) {
                return [
                    'id' => $attendance->id,

                    'student' => [
                        'id' => $attendance->student?->id,
                        'nis' => $attendance->student?->nis,
                        'name' => $attendance->student?->user?->name,
                    ],

                    'class' => [
                        'id' => $attendance->schedule?->schoolClass?->id,
                        'name' => $attendance->schedule?->schoolClass?->name,
                    ],

                    'mapel' => [
                        'id' => $attendance->schedule?->mapel?->id,
                        'name' => $attendance->schedule?->mapel?->name,
                    ],

                    'check_in' => $attendance->check_in,
                    'status' => $attendance->status,
                ];
            });

        $attendanceComposition = Attendance::whereDate(
            'attendance_date',
            $today
        )
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $attendanceComposition = [
            'hadir' => $attendanceComposition['hadir'] ?? 0,
            'terlambat' => $attendanceComposition['terlambat'] ?? 0,
            'izin' => $attendanceComposition['izin'] ?? 0,
            'sakit' => $attendanceComposition['sakit'] ?? 0,
            'alpa' => $attendanceComposition['alpa'] ?? 0,
        ];

        return response()->json([
            'message' => 'Dashboard berhasil diambil.',
            'data' => [
                'summary' => [
                    'total_students' => $totalStudents,
                    'total_teachers' => $totalTeachers,
                    'total_classes' => $totalClasses,
                    'total_rooms' => $totalRooms,
                    'total_mapels' => $totalMapels,
                    'attendance_today_percentage' => $attendanceTodayPercentage,
                    'active_subject_sessions' => $activeSubjectSessions,
                ],

                'live_attendances' => $liveAttendances,

                'attendance_composition' => $attendanceComposition,
            ],
        ]);
    }
}