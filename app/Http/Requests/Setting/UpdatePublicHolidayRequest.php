<?php

namespace App\Http\Requests\Setting;

use App\Enums\RoleEnum;
use App\Models\PublicHoliday;
use App\Rules\UniquePublicHolidayDate;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePublicHolidayRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:120'],
            'date' => ['sometimes', 'date_format:Y-m-d', new UniquePublicHolidayDate($this->ignoreId())],
        ];
    }

    /**
     * @return list<Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('date')) {
                    return;
                }

                $date = $this->input('date');

                if ($date !== null && is_string($date) && ! $this->isRealCalendarDate($date)) {
                    $validator->errors()->add('date', 'The date must be a real calendar date.');
                }
            },
        ];
    }

    private function ignoreId(): ?int
    {
        $route = $this->route('publicHoliday');

        if ($route instanceof PublicHoliday) {
            return $route->getKey();
        }

        return is_numeric($route) ? (int) $route : null;
    }

    private function isRealCalendarDate(string $date): bool
    {
        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)) {
            return false;
        }

        return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]);
    }
}
