<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Models\Schedule;
use App\Models\AttendanceQr;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(){
        $schedules = Schedule::with([
            'teacher.user',
            'mapel',
            'schoolClass',
            'room',
        ])->get();

        return ScheduleResource::collection($schedules);
    }

    public function store(StoreScheduleRequest $request) {
        $schedule = Schedule::create([
            'teacher_id' => $request->teacher_id,
            'mapel_id' => $request->mapel_id,
            'class_id' => $request->class_id,
            'room_id' => $request->room_id,
            'day' => $request->day,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);
    
        $schedule->load([
            'teacher.user',
            'mapel',
            'schoolClass',
            'room',
        ]);

        return response()->json([
            'message' => 'Jadwal berhasil dibuat',
            'data' => new ScheduleResource($schedule),
        ], 201);
    }

    public function show (Schedule $schedule){
        $schedule->load([
            'teacher.user',
            'mapel',
            'schoolClass',
            'room',
        ]);

        return new ScheduleResource($schedule);
    }

    public function update (UpdateScheduleRequest $request, Schedule $schedule) {
        $schedule->update([
            'teacher_id' => $request->teacher_id,
            'mapel_id' => $request->mapel_id,
            'class_id' => $request->class_id,
            'room_id' => $request->room_id,
            'day' => $request->day,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        $schedule->load([
            'teacher.user',
            'mapel',
            'schoolClass',
            'room',
        ]);

        return new ScheduleResource($schedule);
    }

    public function mySchedules(){
        $teacher = auth()->user()->teacher;

        if(!$teacher) {
            return response()->json([
                'message' => 'Akun ini bukan akun guru',
            ], 403);
        }

        $schedules = Schedule::where('teacher_id', $teacher->id)->with([
            'teacher.user',
            'mapel',
            'schoolClass',
            'room',
        ])->get();

        return ScheduleResource::collection($schedules);
    }

    public function deactivate(Schedule $schedule) {
         $schedule->update([
        'is_active' => false,
    ]);

    $schedule->load([
        'teacher.user',
        'mapel',
        'schoolClass',
        'room',
    ]);

    return new ScheduleResource($schedule);
    }

    public function activate(Schedule $schedule)
    {
        $schedule->update([
            'is_active' => true,
        ]);

        $schedule->load([
            'teacher.user',
            'mapel',
            'schoolClass',
            'room',
        ]);

        return new ScheduleResource($schedule);
    }

    public function generateQr(Schedule $schedule)
    {
        $teacher = auth()->user()->teacher;

        if (!$teacher) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun ini bukan akun guru',
            ], 403);
        }

        if ($schedule->teacher_id !== $teacher->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses ke jadwal ini',
            ], 403);
        }

        if (!$schedule->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jadwal tidak aktif',
            ], 422);
        }

        $now = now();

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
                'message' => 'QR hanya dapat dibuat pada hari jadwal berlangsung',
            ], 422);
        }

        $startTime = Carbon::createFromFormat('H:i:s', $schedule->start_time);

        $endTime = Carbon::createFromFormat('H:i:s', $schedule->end_time);

        $currentTime = Carbon::createFromFormat(
            'H:i:s',
            $now->format('H:i:s')
        );

        if ($currentTime->lt($startTime) || $currentTime->gt($endTime)) {
            return response()->json([
                'status' => 'error',
                'message' => 'QR hanya dapat dibuat selama jam jadwal berlangsung',
            ], 422);
        }

        $qr = AttendanceQr::create([
            'schedule_id' => $schedule->id,
            'token' => Str::random(64),
            'expires_at' => $endTime,
            'is_active' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'QR presensi berhasil dibuat',
            'data' => [
                'id' => $qr->id,
                'schedule_id' => $qr->schedule_id,
                'token' => $qr->token,
                'expires_at' => $qr->expires_at,
                'is_active' => $qr->is_active,
            ],
        ], 201);
    }

}
