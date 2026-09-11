<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\ScanQrAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use Illuminate\Http\JsonResponse;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\AttendanceQr;
use App\Models\SchoolSetting;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function store(StoreAttendanceRequest $request): JsonResponse
    {
        $student = auth()->user()->student;

        if (!$student) {
            return response()->json([
                'message' => 'Akun ini bukan akun siswa'
            ], 403);
        }

        if (!auth()->user()->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun siswa tidak aktif',
            ], 403);
        }

        $schedule = Schedule::findOrFail($request->schedule_id);

        $checkIn = Carbon::createFromFormat('H:i', $request->check_in);

        $startTime = Carbon::createFromFormat('H:i:s', $schedule->start_time);

        $lateLimit = $startTime->copy()->addMinutes(15);

        $status = $checkIn->lte($lateLimit)
            ? 'hadir'
            : 'terlambat';

        $attendance = Attendance::create([
            'schedule_id' => $request->schedule_id,
            'student_id' => $student->id,
            'attendance_date' => $request->attendance_date,
            'check_in' => $request->check_in,
            'status' => $status,
        ]);

        return response()->json([
            'message' => 'Presensi Berhasil Dicatat',
            'data' => new AttendanceResource (
                $attendance->load([
                'student.user',
                'schedule.teacher.user',
                'schedule.mapel',
                'schedule.schoolClass',
                'schedule.room',
            ]),
            ),
        ], 201);
    }
    
   public function scanQr(ScanQrAttendanceRequest $request): JsonResponse
    {
    /** @var \App\Models\Student|null $student */
    $student = auth()->user()->student;

    if (!$student) {
        return response()->json([
            'status' => 'error',
            'message' => 'Akun ini bukan akun siswa',
        ], 403);
    }

    /** @var \App\Models\AttendanceQr|null $qr */
    $qr = AttendanceQr::where('token', $request->token)
        ->where('is_active', true)
        ->first();

    if (!$qr) {
        return response()->json([
            'status' => 'error',
            'message' => 'QR presensi tidak valid atau sudah tidak aktif',
        ], 422);
    }

    if (now()->gt($qr->expires_at)) {
        return response()->json([
            'status' => 'error',
            'message' => 'QR presensi sudah kedaluwarsa',
        ], 422);
    }

    /** @var \App\Models\Schedule $schedule */
    $schedule = $qr->schedule;

    if (!$schedule->is_active) {
        return response()->json([
            'status' => 'error',
            'message' => 'Jadwal presensi tidak aktif',
        ], 422);
    }

    if ($student->class_id !== $schedule->class_id) {
        return response()->json([
            'status' => 'error',
            'message' => 'Siswa tidak terdaftar pada kelas jadwal tersebut',
        ], 422);
    }

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

    $today = $dayMap[$now->englishDayOfWeek];

    if ($today !== $schedule->day) {
        return response()->json([
            'status' => 'error',
            'message' => 'QR tidak berlaku pada hari ini',
        ], 422);
    }

    $startTime = Carbon::createFromFormat(
        'H:i:s',
        $schedule->start_time
    );

    $endTime = Carbon::createFromFormat(
        'H:i:s',
        $schedule->end_time
    );

    $currentTime = Carbon::createFromFormat(
        'H:i:s',
        $now->format('H:i:s')
    );

    if ($currentTime->lt($startTime) || $currentTime->gt($endTime)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Presensi berada di luar jam jadwal',
        ], 422);
    }

    $lateLimit = $startTime->copy()->addMinutes(15);

    $status = $now->format('H:i') <= $lateLimit->format('H:i')
        ? 'hadir'
        : 'terlambat';

    $schoolSetting = SchoolSetting::first();

    if (!$schoolSetting) {
        return response()->json([
            'status' => 'error',
            'message' => 'Pengaturan lokasi sekolah belum tersedia',
        ], 500);
    }

    $studentLatitude = (float) $request->latitude;
    $studentLongitude = (float) $request->longitude;

    $schoolLatitude = (float) $schoolSetting->latitude;
    $schoolLongitude = (float) $schoolSetting->longitude;

    $earthRadius = 6371000;

    $latitudeDifference = deg2rad(
        $studentLatitude - $schoolLatitude
    );

    $longitudeDifference = deg2rad(
        $studentLongitude - $schoolLongitude
    );

    $a = sin($latitudeDifference / 2) ** 2
        + cos(deg2rad($schoolLatitude))
        * cos(deg2rad($studentLatitude))
        * sin($longitudeDifference / 2) ** 2;

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    $distance = $earthRadius * $c;

    if ($distance > $schoolSetting->radius) {
        return response()->json([
            'status' => 'error',
            'message' => 'Anda berada di luar area sekolah',
            'data' => [
                'distance' => round($distance, 2),
                'allowed_radius' => $schoolSetting->radius,
            ],
        ], 422);
    }

    $attendanceExists = Attendance::where('schedule_id', $schedule->id)
        ->where('student_id', $student->id)
        ->whereDate('attendance_date', $now->toDateString())
        ->exists();

    if ($attendanceExists) {
        return response()->json([
            'status' => 'error',
            'message' => 'Kamu sudah melakukan presensi untuk jadwal di hari ini',
        ], 422);
    }

    $attendance = Attendance::create([
        'schedule_id' => $schedule->id,
        'student_id' => $student->id,
        'attendance_date' => $now->toDateString(),
        'check_in' => $now->format('H:i'),
        'status' => $status,
    ]);

    $attendance->load([
        'student.user',
        'schedule.teacher.user',
        'schedule.mapel',
        'schedule.schoolClass',
        'schedule.room',
    ]);
        
    return response()->json([
        'status' => 'success',
        'message' => 'Presensi berhasil dicatat',
        'data' => [
            'attendance' => new AttendanceResource($attendance),
            'location' => [
                'distance' => round($distance, 2),
                'allowed_radius' => $schoolSetting->radius,
            ],
        ],
    ], 201);
}
}
