<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\ActiveClass;
use App\Rules\ActiveMapel;
use App\Rules\ActiveRoom;
use App\Rules\ActiveTeacher;
use App\Rules\ScheduleConflict;
use App\Rules\TeacherTeachesMapel;

class UpdateScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $schedule = $this->route('schedule');
        return [
            'teacher_id' => [
                'required',
                'integer',
                'exists:teachers,id', new ActiveTeacher, new ScheduleConflict(
                    'teacher_id',
                    $this->teacher_id,
                    $this->day,
                    $this->start_time,
                    $this->end_time,
                    $schedule->id
                ),
            ],

            'mapel_id' => [
                'required',
                'integer',
                'exists:mapels,id',
                new ActiveMapel,
                new TeacherTeachesMapel($this->teacher_id),
            ],

            'class_id' => [
                'required',
                'integer',
                'exists:classes,id',
                new ActiveClass,
                new ScheduleConflict(
                    'class_id',
                    $this->class_id,
                    $this->day,
                    $this->start_time,
                    $this->end_time,
                    $schedule->id
                ),
            ],

            'room_id' => [
                'required',
                'integer',
                'exists:rooms,id',
                new ActiveRoom,
                new ScheduleConflict(
                    'room_id',
                    $this->room_id,
                    $this->day,
                    $this->start_time,
                    $this->end_time,
                    $schedule->id
                ),
            ],
            
            'day' => [
                'required',
                Rule::in([
                    'Senin',
                    'Selasa',
                    'Rabu',
                    'Kamis',
                    'Jumat',
                    'Sabtu',
                ]),
            ],

            'start_time' => [
                'required',
                'date_format:H:i',
            ],

            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
            ],
        ];
    }
    
}
