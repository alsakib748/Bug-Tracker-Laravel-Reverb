<?php

namespace App\Http\Requests\Ai;

use App\Enums\IssueType;
use App\Enums\Priority;
use App\Enums\Severity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnalyzeBugReportRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'type' => ['nullable', Rule::enum(IssueType::class)],
            'severity' => ['nullable', Rule::enum(Severity::class)],
            'priority' => ['nullable', Rule::enum(Priority::class)],
            'expected_behavior' => ['nullable', 'string', 'max:5000'],
            'actual_behavior' => ['nullable', 'string', 'max:5000'],
            'reproduction_steps' => ['nullable', 'array', 'max:50'],
            'reproduction_steps.*' => ['string', 'max:500'],
            'environment' => ['nullable', 'string', 'max:500'],
            'issue_id' => ['nullable', 'integer', 'exists:issues,id'],
        ];
    }
}
