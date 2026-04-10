<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SyncVitalRequest extends FormRequest
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
            'hrv_score' => ['required', 'numeric', 'min:0', 'max:200'],
            'heart_rate' => ['required', 'integer', 'min:30', 'max:250'],
            'stress_index' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'device_type' => ['nullable', 'string', 'max:50'],
            'is_simulated' => ['boolean'],
            'recorded_at' => ['nullable', 'date'],
        ];
    }
}
