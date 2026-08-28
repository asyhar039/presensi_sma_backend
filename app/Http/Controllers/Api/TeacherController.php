<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeacherResource;
use App\Http\Resources\MapelResource;
use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Http\Requests\AssignTeacherMapelRequest;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Mapel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TeacherController extends Controller
{
    
    public function index()
    {
        $teachers = Teacher::with('user')->get();
        return TeacherResource::collection($teachers);
    }

    public function store(StoreTeacherRequest $request)
{
      
    $teacher = DB::transaction(function () use ($request) {

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        $user->assignRole('Guru');

        return Teacher::create([
            'user_id' => $user->id,
            'nip' => $request->nip,
            'gender' => $request->gender,
            'phone' => $request->phone,
            'birth_date' => $request->birth_date,
        ]);
    });

    return new TeacherResource(
        $teacher->load('user')
    );
}

    public function show(Teacher $teacher)
    {
        $teacher->load('user');
        return new TeacherResource($teacher);
    }

    public function update(UpdateTeacherRequest $request, Teacher $teacher)
    {
        DB::transaction(function () use ($request, $teacher) {
            $teacher->user->update([
                'name' => $request->name,
                'email' => $request->email,                
            ]);

            $teacher->update([
                'nip' => $request->nip,
                'gender' => $request->gender,
                'phone' => $request->phone,
                'birth_date' => $request->birth_date,
            ]);

            if ($request->filled('password')) {
                $teacher->user->update([
                    'password' => Hash::make($request->password),
                ]);
            }
        });
        return new TeacherResource($teacher->load('user'));
    }

        public function deactivate(Teacher $teacher)
    {
        $teacher->user->update([
            'is_active' => false,
        ]);

        return new TeacherResource(
            $teacher->load('user')
        );
    }

    public function activate(Teacher $teacher)
    {
        $teacher->user->update([
            'is_active' => true,
        ]);

        return new TeacherResource(
            $teacher->load('user')
        );
    }

    public function assignMapel(AssignTeacherMapelRequest $request, Teacher $teacher)
    {
        $teacher->mapels()->attach($request->mapel_id);

        return response()->json([
            'message' => 'Mapel Berhasil Ditambahkan ke guru',
        ]);
        
    }

    public function mapels(Teacher $teacher)
    {
        $mapels = $teacher->mapels()->get();
        return MapelResource::collection($mapels);
    }

    public function removeMapel(Teacher $teacher, Mapel $mapel)
    {
        $deleted = $teacher->mapels()->detach($mapel->id);

         if ($deleted === 0) {
        return response()->json([
            'message' => 'Mapel tersebut tidak ditugaskan kepada guru ini.',
        ], 404);
        }

        return response()->json([
            'message' => 'Mapel berhasil dihapus dari guru.',
        ]);
    }
}

