<?php

namespace App\Services\Teacher;

use App\Enums\RoleEnum;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TeacherService
{
    /**
     * @return LengthAwarePaginator<int, Teacher>
     */
    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return Teacher::query()
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
    public function create(array $data): Teacher
    {
        return DB::transaction(function () use ($data): Teacher {
            $user = User::query()->create([
                'identity_number' => $data['identity_number'],
                'name' => $data['name'],
                'email' => $data['email'],
                'phone_number' => $data['phone_number'] ?? null,
                'password' => $data['password'],
            ]);
            $user->assignRole(RoleEnum::Teacher->value);

            $teacher = Teacher::query()->create([
                'user_id' => $user->id,
                'gender' => $data['gender'],
                'address' => $data['address'] ?? null,
                'employment_status' => $data['employment_status'],
            ]);

            return $teacher->load('user');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Teacher $teacher, array $data): Teacher
    {
        return DB::transaction(function () use ($teacher, $data): Teacher {
            $userData = array_intersect_key($data, array_flip(['identity_number', 'name', 'email', 'phone_number']));
            if ($userData !== []) {
                $teacher->user()->update($userData);
            }

            $teacherData = array_intersect_key($data, array_flip(['gender', 'address', 'employment_status']));
            if ($teacherData !== []) {
                $teacher->fill($teacherData);
                $teacher->save();
            }

            return $teacher->load('user');
        });
    }

    public function delete(Teacher $teacher): void
    {
        DB::transaction(function () use ($teacher): void {
            $user = $teacher->user;
            $teacher->delete();
            $user?->delete();
        });
    }

    public function changePassword(Teacher $teacher, string $password): Teacher
    {
        $user = $teacher->user;
        $user->password = $password;
        $user->save();

        return $teacher->load('user');
    }
}
