<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Models\Schedule;
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

}
