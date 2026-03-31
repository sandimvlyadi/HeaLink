<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreMoodJournalRequest extends FormRequest
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
            'emoji'        => ['nullable', 'string', 'max:10'],
            'mood'         => ['required', 'string', 'in:very_bad,bad,neutral,good,very_good'],
            'note'         => ['nullable', 'string', 'max:2000'],
            'journal_date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
