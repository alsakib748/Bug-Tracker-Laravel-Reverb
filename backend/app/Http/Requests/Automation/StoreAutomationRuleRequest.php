<?php

namespace App\Http\Requests\Automation;

use Illuminate\Foundation\Http\FormRequest;

class StoreAutomationRuleRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'trigger_type' => ['required', 'string'],
            'conditions' => ['required', 'array'],
            'conditions.match' => ['required', 'in:all,any'],
            'conditions.rules' => ['present', 'array', 'max:20'],
            'conditions.rules.*.field' => ['required', 'string'],
            'conditions.rules.*.operator' => ['required', 'string'],
            'actions' => ['required', 'array', 'min:1', 'max:10'],
            'actions.*.type' => ['required', 'string'],
            'actions.*.config' => ['required', 'array'],
            'is_enabled' => ['boolean'],
            'run_on_actor_changes' => ['boolean'],
            'cooldown_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
        ];
    }
}
