<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreSleepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'duration_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'quality_score'    => ['required', 'numeric', 'min:0', 'max:10'],
            'quality_category' => ['nullable', 'string', 'in:poor,fair,good'],
            'sleep_time'       => ['nullable', 'date_format:H:i:s'],
            'wake_time'        => ['nullable', 'date_format:H:i:s'],
            'sleep_date'       => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
