<?php

namespace App\Services\Teacher;

use App\Enums\Enums\GenderEnums;
use App\Enums\Enums\TeacherEmploymentStatusEnums;
use App\Enums\RoleEnum;
use App\Models\Teacher;
use App\Models\User;
use App\Services\DataTable\DataTableBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherService
{
    /**
     * List teachers searchable by user name or identity number, filterable by employment status and gender.
     *
     * @return LengthAwarePaginator<int, Teacher>
     */
    public function paginate(Request $request): LengthAwarePaginator
    {
        $employmentStatuses = implode(',', array_column(TeacherEmploymentStatusEnums::cases(), 'value'));
        $genders = implode(',', array_column(GenderEnums::cases(), 'value'));

        return DataTableBuilder::make(Teacher::query(), $request)
            ->with('user')
            ->searchable(['user.name', 'user.identity_number'])
            ->sortable([
                'id' => 'id',
                'name' => function (Builder $query, string $direction): void {
                    $query->orderBy(User::select('name')->whereColumn('users.id', 'teachers.user_id'), $direction);
                },
                'created_at' => 'created_at',
            ])
            ->addFilter('employment_status', ['nullable', 'string', 'in:'.$employmentStatuses], function (Builder $query, string $value): void {
                $query->where('employment_status', $value);
            })
            ->addFilter('gender', ['nullable', 'string', 'in:'.$genders], function (Builder $query, string $value): void {
                $query->where('gender', $value);
            })
            ->paginate();
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
