<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ActiveSchedule;
use App\Rules\ActiveStudent;
use App\Rules\StudentInScheduleClass;
use App\Rules\AttendanceAlreadyExists;
use App\Rules\AttendanceWithinSchedule;

class StoreAttendanceRequest extends FormRequest
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
        return [
            'schedule_id' => ['required','exists:schedules,id', new ActiveSchedule,],
            'student_id' => ['required','exists:students,id', new ActiveStudent, new StudentInScheduleClass($this->schedule_id),
            new AttendanceAlreadyExists(
                $this->schedule_id,
                $this->attendance_date
            ),],
            'attendance_date' => ['required','date',],
            'check_in' => ['required','date_format:H:i', new AttendanceWithinSchedule(
                $this->schedule_id,
                $this->attendance_date,
                $this->check_in
            ),],
        
        ];
    }
}
