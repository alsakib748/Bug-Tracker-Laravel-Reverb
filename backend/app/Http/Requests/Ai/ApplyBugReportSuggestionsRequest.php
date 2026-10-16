<?php

namespace App\Http\Requests\Ai;

use App\Enums\Priority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplyBugReportSuggestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'accepted' => ['required', 'array'],
            'accepted.title' => ['boolean'],
            'accepted.description' => ['boolean'],
            'accepted.priority' => ['boolean'],
            'accepted.label_ids' => ['array'],
            'accepted.label_ids.*' => ['integer', 'exists:labels,id'],

            // Optional user-edited overrides applied instead of the suggestion.
            'reviewed_title' => ['nullable', 'string', 'max:255'],
            'reviewed_description' => ['nullable', 'string', 'max:10000'],
            'reviewed_priority' => ['nullable', Rule::enum(Priority::class)],
        ];
    }
}
