<?php

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
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
        $projectId = $this->route('project')->id ?? null;

        return [
            'name' => 'sometimes|string|max:255',
            'code' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('projects', 'code')->ignore($projectId),
            ],
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'status' => ['nullable', Rule::enum(ProjectStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'This project code is already taken.',
            'status.enum' => 'Invalid project status.',
        ];
    }

}