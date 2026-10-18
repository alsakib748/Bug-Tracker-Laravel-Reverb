<?php

namespace App\Http\Requests\Automation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAutomationRuleRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'trigger_type' => ['sometimes', 'string'],
            'conditions' => ['sometimes', 'array'],
            'conditions.match' => ['required_with:conditions', 'in:all,any'],
            'conditions.rules' => ['present_with:conditions', 'array', 'max:20'],
            'conditions.rules.*.field' => ['required', 'string'],
            'conditions.rules.*.operator' => ['required', 'string'],
            'actions' => ['sometimes', 'array', 'min:1', 'max:10'],
            'actions.*.type' => ['required', 'string'],
            'actions.*.config' => ['required', 'array'],
            'is_enabled' => ['sometimes', 'boolean'],
            'run_on_actor_changes' => ['sometimes', 'boolean'],
            'cooldown_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
        ];
    }
}
