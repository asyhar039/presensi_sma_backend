<?php

namespace App\Http\Requests\Setting;

use App\Enums\RoleEnum;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSchoolZonesRequest extends FormRequest
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
            '*.points' => ['required', 'array', 'min:3'],
            '*.points.*' => ['required', 'array', 'size:2'],
            '*.points.*.*' => ['required', 'numeric'],
            '*.points.*.0' => ['numeric', 'between:-90,90'],
            '*.points.*.1' => ['numeric', 'between:-180,180'],
        ];
    }

    /**
     * @return list<Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $zones = $this->all();

                if (! is_array($zones) || ! array_is_list($zones)) {
                    $validator->errors()->add('zones', 'The payload must be a JSON array of zones.');

                    return;
                }

                foreach (array_values($zones) as $index => $zone) {
                    if (! is_array($zone) || ! isset($zone['points']) || ! is_array($zone['points'])) {
                        continue;
                    }

                    $normalized = array_map(
                        fn (mixed $point): string => implode(',', [(float) $point[0], (float) $point[1]]),
                        array_values($zone['points'])
                    );

                    if (count($normalized) !== count(array_unique($normalized))) {
                        $validator->errors()->add("{$index}.points", 'The points must not contain duplicates.');
                    }
                }
            },
        ];
    }
}
