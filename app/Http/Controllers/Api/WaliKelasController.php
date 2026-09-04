<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class WaliKelasController extends Controller
{
    public function class(): JsonResponse
    {
        $user = $this->authUser();
        $teacher = $user->teacher;
        $schoolClass = $teacher?->schoolClass;

        if (!$schoolClass) {
            return response()->json([
                'message' => 'Anda Bukan Wali Kelas',
            ], 403);
        }

        return response()->json([
            'message' => 'Data Wali Kelas Berhasil Diambil',
            'data' => [
                'id' => $schoolClass->id,
                'name' => $schoolClass->name,
                'wali_kelas' => [
                    'id' => $teacher->id,
                    'name' => $teacher->user->name,
                ],
            ],
        ]);
    }

    public function students(): JsonResponse
    {
        $user = $this->authUser();

        $teacher = $user->teacher;

        $schoolClass = $teacher?->schoolClass;

        if (!$schoolClass) {
            return response()->json([
                'message' => 'Anda bukan wali kelas.',
            ], 403);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Student> $students */
        $students = $schoolClass->students()
            ->with('user')
            ->get();

        return response()->json([
            'message' => 'Daftar siswa kelas berhasil diambil.',
            'data' => [
                'class' => [
                    'id' => $schoolClass->id,
                    'name' => $schoolClass->name,
                ],
                'students' => $students->map(function (Student $student) {
                    return [
                        'id' => $student->id,
                        'nis' => $student->nis,
                        'name' => $student->user->name,
                    ];
                }),
            ],
        ]);
    }

    public function attendances(): JsonResponse
    {
       $user = $this->authUser();
       $teacher = $user->teacher;
       $schoolClass = $teacher?->schoolClass;
       
       if(!$schoolClass) {
        return response()->json([
            'message' => 'Anda bukan wali kelas',
        ], 403);
       }

       /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Student> $students */
       $students = $schoolClass->students()
        ->with([
            'user',
            'attendances.schedule.mapel',
        ])
        ->get();

    return response()->json([
        'message' => 'Rekap presensi kelas berhasil diambil.',
        'data' => [
            'class' => [
                'id' => $schoolClass->id,
                'name' => $schoolClass->name,
            ],

            'students' => $students->map(function (Student $student) {
                return [
                    'id' => $student->id,
                    'nis' => $student->nis,
                    'name' => $student->user->name,

                    'attendances' => $student->attendances
                        ->map(function (Attendance $attendance) {
                            return [
                                'id' => $attendance->id,
                                'attendance_date' => $attendance->attendance_date,
                                'check_in' => $attendance->check_in,
                                'status' => $attendance->status,

                                'mapel' => [
                                    'id' => $attendance->schedule->mapel->id,
                                    'name' => $attendance->schedule->mapel->name,
                                ],
                            ];
                        })
                        ->values(),
                ];
            })->values(),
        ],
    ]);
}

    public function attendanceSummary(): JsonResponse
    {
        $user = $this->authUser();
        $teacher = $user->teacher;
        $schoolClass = $teacher?->schoolClass;

        if (!$schoolClass) {
            return response()->json([
                'message' => 'Anda bukan wali kelas.',
            ], 403);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Student> $students */
        $students = $schoolClass->students()
            ->with('user')
            ->get();

        $studentIds = $students->pluck('id');

        $attendances = Attendance::whereIn('student_id', $studentIds)
            ->get();

        return response()->json([
            'message' => 'Ringkasan presensi kelas berhasil diambil',
            'data' => [
                'class' => [
                    'id' => $schoolClass->id,
                    'name' => $schoolClass->name,
                ],

                'students' => $students->map(function (Student $student) use ($attendances) {

                    $studentAttendances = $attendances
                        ->where('student_id', $student->id);

                    $hadir = $studentAttendances
                        ->where('status', 'hadir')
                        ->count();

                    $terlambat = $studentAttendances
                        ->where('status', 'terlambat')
                        ->count();

                    $izin = $studentAttendances
                        ->where('status', 'izin')
                        ->count();

                    $sakit = $studentAttendances
                        ->where('status', 'sakit')
                        ->count();

                    $alpa = $studentAttendances
                        ->where('status', 'alpa')
                        ->count();

                    $total = $studentAttendances->count();

                    $attendancePercentage = $total > 0
                        ? round((($hadir + $terlambat) / $total) * 100, 1)
                        : 0;

                    return [
                        'id' => $student->id,
                        'nis' => $student->nis,
                        'name' => $student->user->name,

                        'summary' => [
                            'hadir' => $hadir,
                            'terlambat' => $terlambat,
                            'izin' => $izin,
                            'sakit' => $sakit,
                            'alpa' => $alpa,
                            'total' => $total,
                            'attendance_percentage' => $attendancePercentage,
                        ],
                    ];
                })->values(),
            ],
        ]);
    }
}
