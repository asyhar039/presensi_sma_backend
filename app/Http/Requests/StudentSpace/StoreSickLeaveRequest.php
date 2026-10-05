<?php

namespace App\Http\Requests\StudentSpace;

class StoreSickLeaveRequest extends BaseLeaveRequestRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d'],
            'notes' => ['nullable', 'string', 'max:255'],
            'attachment' => $this->attachmentRules(),
        ];
    }
}
