<?php

namespace App\Http\Requests\Setting;

use App\Enums\RoleEnum;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePublicHolidaysRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleEnum::Admin) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:120', 'distinct'],
            '*.date' => ['required', 'date_format:Y-m-d', 'distinct'],
        ];
    }

    /**
     * @return list<Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $holidays = $this->all();

                if (! is_array($holidays) || ! array_is_list($holidays)) {
                    $validator->errors()->add('holidays', 'The payload must be a JSON array of holidays.');

                    return;
                }

                foreach (array_values($holidays) as $index => $holiday) {
                    if (! is_array($holiday) || ! isset($holiday['date']) || ! is_string($holiday['date'])) {
                        continue;
                    }

                    if (! $this->isRealCalendarDate($holiday['date'])) {
                        $validator->errors()->add("{$index}.date", 'The date must be a real calendar date.');
                    }
                }
            },
        ];
    }

    private function isRealCalendarDate(string $date): bool
    {
        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)) {
            return false;
        }

        return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]);
    }
}
