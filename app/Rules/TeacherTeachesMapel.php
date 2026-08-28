<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use App\Models\Teacher;

class TeacherTeachesMapel implements ValidationRule
{
    public function __construct(
        protected mixed $teacherId
    ){

    }
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $teacher = Teacher::find($this->teacherId);

        if (!$teacher) {
            return;
        }

        $hasMapel = $teacher->mapels()->where('mapels.id', $value)->exists();

        if (!$hasMapel) {
            $fail('Guru tersebut tidak mengampu mapel yang kamu pilih');
        }
    }
}
