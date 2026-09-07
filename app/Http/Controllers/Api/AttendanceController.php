<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use Illuminate\Http\JsonResponse;
use App\Models\Attendance;
use App\Models\Schedule;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function store(StoreAttendanceRequest $request): JsonResponse
    {

        $schedule = Schedule::findOrFail($request->schedule_id);

        $checkIn = Carbon::createFromFormat('H:i', $request->check_in);

        $startTime = Carbon::createFromFormat('H:i:s', $schedule->start_time);

        $lateLimit = $startTime->copy()->addMinutes(15);

        $status = $checkIn->lte($lateLimit)
            ? 'hadir'
            : 'terlambat';

        $attendance = Attendance::create([
            'schedule_id' => $request->schedule_id,
            'student_id' => $request->student_id,
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
}
