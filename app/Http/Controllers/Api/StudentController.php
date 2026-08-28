<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with('user', 'schoolClass')->get();

        return StudentResource::collection($students);
    }

    public function store(StoreStudentRequest $request)
    {
        $student = DB::transaction(function () use ($request) {

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_active' => true,
            ]);

            $user->assignRole('Siswa');

            return Student::create([
                'user_id' => $user->id,
                'nis' => $request->nis,
                'gender' => $request->gender,
                'phone' => $request->phone,
                'birth_date' => $request->birth_date,
                'class_id' => $request->class_id,
            ]);
        });

        return new StudentResource(
            $student->load('user', 'schoolClass')
        );
    }

    public function show(Student $student)
    {
        return new StudentResource(
            $student->load('user', 'schoolClass')
        );
    }

    public function update(UpdateStudentRequest $request, Student $student)
    {
        DB::transaction(function () use ($request, $student) {
            $user = $student->user;

            $user->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);

            if ($request->filled('password')) {
                $user->update([
                    'password' => Hash::make($request->password),
                ]);
            }

            $student->update([
                'nis' => $request->nis,
                'gender' => $request->gender,
                'phone' => $request->phone,
                'birth_date' => $request->birth_date,
                'class_id' => $request->class_id,
            ]);

        });

        return new StudentResource(
            $student->fresh()->load('user', 'schoolClass')
        );
    }
    public function destroy(Student $student)
    {
        DB::transaction(function () use ($student) {
            $user = $student->user;
            $student->delete();
            $user->delete();
        });

        return response()->json([
            'message' => 'Data Siswa Berhasil Dihapus.',
        ]);
    }
    
}
