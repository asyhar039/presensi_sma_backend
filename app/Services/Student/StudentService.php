<?php

namespace App\Services\Student;

use App\Enums\RoleEnum;
use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class StudentService
{
    /**
     * @return LengthAwarePaginator<int, Student>
     */
    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return Student::query()
            ->with('user')
            ->when($search, function ($query) use ($search): void {
                $query->whereHas('user', function ($userQuery) use ($search): void {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('identity_number', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Student
    {
        return DB::transaction(function () use ($data): Student {
            $user = User::query()->create([
                'identity_number' => $data['identity_number'],
                'name' => $data['name'],
                'email' => $data['email'],
                'phone_number' => $data['phone_number'] ?? null,
                'password' => $data['password'],
            ]);
            $user->assignRole(RoleEnum::Student->value);

            $student = Student::query()->create([
                'user_id' => $user->id,
                'gender' => $data['gender'],
                'address' => $data['address'] ?? null,
                'status' => $data['status'],
            ]);

            return $student->load('user');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Student $student, array $data): Student
    {
        return DB::transaction(function () use ($student, $data): Student {
            $userData = array_intersect_key($data, array_flip(['identity_number', 'name', 'email', 'phone_number']));
            if ($userData !== []) {
                $student->user()->update($userData);
            }

            $studentData = array_intersect_key($data, array_flip(['gender', 'address', 'status']));
            if ($studentData !== []) {
                $student->fill($studentData);
                $student->save();
            }

            return $student->load('user');
        });
    }

    public function delete(Student $student): void
    {
        DB::transaction(function () use ($student): void {
            $user = $student->user;
            $student->delete();
            $user?->delete();
        });
    }

    public function changePassword(Student $student, string $password): Student
    {
        $user = $student->user;
        $user->password = $password;
        $user->save();

        return $student->load('user');
    }
}
