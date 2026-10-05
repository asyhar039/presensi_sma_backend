<?php

namespace App\Http\Requests\StudentSpace;

class StoreEarlyOutRequest extends BaseLeaveRequestRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'time_out' => ['required', 'date_format:H:i'],
            'time_in' => ['required', 'date_format:H:i', 'after:time_out'],
            'exit_reason' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:128'],
            'contact_person' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:255'],
            'attachment' => $this->attachmentRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['time_in.after' => 'The return time must be after the exit time.'];
    }
}
