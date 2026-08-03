<?php

namespace App\Http\Requests;

use App\Enums\IssueStatus;
use App\Enums\IssueType;
use App\Enums\Priority;
use App\Enums\Severity;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIssueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_id' => 'sometimes|exists:projects,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'priority' => ['sometimes', Rule::enum(Priority::class)],
            'severity' => ['sometimes', Rule::enum(Severity::class)],
            // 'status' => ['sometimes', Rule::enum(IssueStatus::class)],
            'type' => ['sometimes', Rule::enum(IssueType::class)],
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date|after:today',
            'estimated_hours' => 'nullable|numeric|min:0.5|max:999',
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.exists' => 'Project does not exist.',
            'assigned_to.exists' => 'The selected assignee does not exist.',
            'due_date.after' => 'Due date must be in the future.',
            'estimated_hours.min' => 'Estimated hours must be at least 0.5.',
        ];
    }

}
