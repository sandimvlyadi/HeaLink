<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreScreeningRequest extends FormRequest
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
            'height_cm'    => ['nullable', 'numeric', 'min:50', 'max:300'],
            'weight_kg'    => ['nullable', 'numeric', 'min:10', 'max:500'],
            'systolic'     => ['nullable', 'integer', 'min:50', 'max:300'],
            'diastolic'    => ['nullable', 'integer', 'min:30', 'max:200'],
            'phq9_answers' => ['nullable', 'array', 'size:9'],
            'phq9_answers.*' => ['integer', 'min:0', 'max:3'],
        ];
    }
}
