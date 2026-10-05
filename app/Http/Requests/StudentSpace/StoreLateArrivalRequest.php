<?php

namespace App\Http\Requests\StudentSpace;

class StoreLateArrivalRequest extends BaseLeaveRequestRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'estimated_arrival_time' => ['required', 'date_format:H:i'],
            'late_reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:255'],
            'attachment' => $this->attachmentRules(),
        ];
    }
}
