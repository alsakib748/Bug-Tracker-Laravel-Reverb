<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncIssueLabelsRequest extends FormRequest
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
            'label_ids' => ['present', 'array'],
            'label_ids.*' => ['integer', 'exists:labels,id'],
        ];
    }
}
