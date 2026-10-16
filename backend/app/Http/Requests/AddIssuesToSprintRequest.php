<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddIssuesToSprintRequest extends FormRequest
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
            'issue_ids' => ['required', 'array', 'min:1'],
            'issue_ids.*' => ['integer', Rule::exists('issues', 'id')],
        ];
    }
}
