<?php

namespace App\Http\Requests\Setting;

use App\Enums\RoleEnum;
use App\Models\PublicHoliday;
use App\Rules\RealCalendarDate;
use App\Rules\UniquePublicHolidayDate;
use Illuminate\Foundation\Http\FormRequest;

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
            'date' => ['sometimes', 'date_format:Y-m-d', new RealCalendarDate, new UniquePublicHolidayDate($this->ignoreId())],
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
}
