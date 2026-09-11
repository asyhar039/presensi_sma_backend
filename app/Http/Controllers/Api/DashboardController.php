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
            fn (Schedule $schedule) =>
                $schedule->schoolClass->students->count()
        );

        $todayScheduleIds = $todaySchedules->pluck('id');

        $todayAttendances = Attendance::whereDate(
            'attendance_date',
            $today
        )
            ->whereIn('schedule_id', $todayScheduleIds)
            ->count();

        $attendanceTodayPercentage = $expectedAttendances > 0
            ? round(($todayAttendances / $expectedAttendances) * 100, 1)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | 4. Sesi Mapel yang Sedang Berlangsung
        |--------------------------------------------------------------------------
        */

        $activeSubjectSessions = Schedule::where('is_active', true)
            ->where('day', $todayDay)
            ->whereTime(
                'start_time',
                '<=',
                $now->format('H:i:s')
            )
            ->whereTime(
                'end_time',
                '>=',
                $now->format('H:i:s')
            )
            ->count();


        /** @var \Illuminate\Database\Eloquent\Collection<int, Attendance> $liveAttendances */
        $liveAttendances = Attendance::with([
            'student.user',
            'schedule.teacher.user',
            'schedule.mapel',
            'schedule.schoolClass',
        ])
            ->whereDate('attendance_date', $today)
            ->latest('check_in')
            ->take(10)
            ->get();

        $liveAttendances = $liveAttendances->map(
            function (Attendance $attendance): array {
                return [
                    'id' => $attendance->id,

                    'student' => [
                        'id' => $attendance->student->id,
                        'nis' => $attendance->student->nis,
                        'name' => $attendance->student->user->name,
                    ],

                    'class' => [
                        'id' => $attendance->schedule->schoolClass->id,
                        'name' => $attendance->schedule->schoolClass->name,
                    ],

                    'mapel' => [
                        'id' => $attendance->schedule->mapel->id,
                        'name' => $attendance->schedule->mapel->name,
                    ],

                    'check_in' => $attendance->check_in,
                    'status' => $attendance->status,
                ];
            }
        )->values();

    
        $attendanceCompositionData = Attendance::whereDate(
            'attendance_date',
            $today
        )
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

      
        $attendanceComposition = [
            [
                'status' => 'hadir',
                'label' => 'Hadir',
                'total' => (int) ($attendanceCompositionData['hadir'] ?? 0),
            ],
            [
                'status' => 'terlambat',
                'label' => 'Terlambat',
                'total' => (int) ($attendanceCompositionData['terlambat'] ?? 0),
            ],
            [
                'status' => 'izin',
                'label' => 'Izin',
                'total' => (int) ($attendanceCompositionData['izin'] ?? 0),
            ],
            [
                'status' => 'sakit',
                'label' => 'Sakit',
                'total' => (int) ($attendanceCompositionData['sakit'] ?? 0),
            ],
            [
                'status' => 'alpa',
                'label' => 'Alpa',
                'total' => (int) ($attendanceCompositionData['alpa'] ?? 0),
            ],
        ];


        $summary = [
            [
                'key' => 'total_students',
                'label' => 'Total Siswa',
                'value' => $totalStudents,
            ],
            [
                'key' => 'total_teachers',
                'label' => 'Total Guru',
                'value' => $totalTeachers,
            ],
            [
                'key' => 'total_classes',
                'label' => 'Total Kelas',
                'value' => $totalClasses,
            ],
            [
                'key' => 'total_rooms',
                'label' => 'Total Ruangan',
                'value' => $totalRooms,
            ],
            [
                'key' => 'total_mapels',
                'label' => 'Total Mapel',
                'value' => $totalMapels,
            ],
            [
                'key' => 'attendance_today_percentage',
                'label' => 'Presensi Hari Ini',
                'value' => $attendanceTodayPercentage,
            ],
            [
                'key' => 'active_subject_sessions',
                'label' => 'Sesi Mapel Aktif',
                'value' => $activeSubjectSessions,
            ],
        ];

       return response()->json([
            'message' => 'Dashboard berhasil diambil.',

            'data' => [
                'summary' => $summary,

                'live_attendances' => $liveAttendances,

                'attendance_composition' => $attendanceComposition,
            ],
        ]);
    }
}