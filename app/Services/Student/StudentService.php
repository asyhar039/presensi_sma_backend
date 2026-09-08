<?php

namespace App\Services\Student;

use App\Enums\Enums\GenderEnums;
use App\Enums\Enums\StudentStatusEnums;
use App\Enums\RoleEnum;
use App\Models\Student;
use App\Models\User;
use App\Services\DataTable\DataTableBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentService
{
    /**
     * List students searchable by user name or identity number, filterable by status and gender.
     *
     * @return LengthAwarePaginator<int, Student>
     */
    public function paginate(Request $request): LengthAwarePaginator
    {
        $statuses = implode(',', array_column(StudentStatusEnums::cases(), 'value'));
        $genders = implode(',', array_column(GenderEnums::cases(), 'value'));

        return DataTableBuilder::make(Student::query(), $request)
            ->with('user')
            ->searchable(['user.name', 'user.identity_number'])
            ->sortable([
                'id' => 'id',
                'name' => function (Builder $query, string $direction): void {
                    $query->orderBy(User::select('name')->whereColumn('users.id', 'students.user_id'), $direction);
                },
                'created_at' => 'created_at',
            ])
            ->addFilter('status', ['nullable', 'string', 'in:'.$statuses], function (Builder $query, string $value): void {
                $query->where('status', $value);
            })
            ->addFilter('gender', ['nullable', 'string', 'in:'.$genders], function (Builder $query, string $value): void {
                $query->where('gender', $value);
            })
            ->paginate();
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
