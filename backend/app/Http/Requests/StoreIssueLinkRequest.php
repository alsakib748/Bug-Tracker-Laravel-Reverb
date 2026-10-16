<?php

namespace App\Http\Requests;

use App\Enums\IssueLinkType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIssueLinkRequest extends FormRequest
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
            'target_issue_id' => ['required', 'integer', 'exists:issues,id'],
            'link_type' => ['required', Rule::enum(IssueLinkType::class)],
        ];
    }
}
