<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteSprintRequest extends FormRequest
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
            'carry_over' => ['required', Rule::in(['backlog', 'move_to_sprint'])],
            'target_sprint_id' => [
                'nullable',
                'integer',
                'required_if:carry_over,move_to_sprint',
                Rule::exists('sprints', 'id'),
            ],
        ];
    }
}
