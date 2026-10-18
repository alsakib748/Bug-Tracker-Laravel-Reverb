<?php

namespace App\Http\Requests\GitHub;

use Illuminate\Foundation\Http\FormRequest;

class ConnectGitHubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('project')) ?? false;
    }

    public function rules(): array
    {
        return [
            'installation_id' => ['required', 'integer', 'min:1'],
            'account' => ['sometimes', 'array'],
            'account.login' => ['sometimes', 'string', 'max:100'],
            'account.type' => ['sometimes', 'string', 'max:30'],
        ];
    }
}
