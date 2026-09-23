<?php

namespace App\Http\Requests\Setting;

use App\Enums\RoleEnum;
use App\Rules\RealCalendarDate;
use App\Rules\UniquePublicHolidayDate;
use Illuminate\Foundation\Http\FormRequest;

class StorePublicHolidayRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'date' => ['required', 'date_format:Y-m-d', new RealCalendarDate, new UniquePublicHolidayDate],
        ];
    }
}
