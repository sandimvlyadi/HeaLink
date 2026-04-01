<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsultationRequest extends FormRequest
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
            'medic_id' => ['required', 'string', Rule::exists('users', 'uuid')->where('role', 'medic')],
            'scheduled_at' => ['required', 'date', 'after:now'],
        ];
    }
}
