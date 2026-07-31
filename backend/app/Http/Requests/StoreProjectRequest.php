<?php

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:projects,code',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'status' => ['nullable', Rule::enum(ProjectStatus::class)]
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Project name is required.',
            'code.required' => 'Project code is required.',
            'code.unique' => 'This project code is already exist.',
            'status.enum' => 'Invalid project status.',
        ];
    }

}
