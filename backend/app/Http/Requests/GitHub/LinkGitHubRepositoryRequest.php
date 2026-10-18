<?php

namespace App\Http\Requests\GitHub;

use Illuminate\Foundation\Http\FormRequest;

class LinkGitHubRepositoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('project')) ?? false;
    }

    public function rules(): array
    {
        return [
            'github_repository_id' => ['required', 'integer', 'exists:github_repositories,id'],
            'is_default' => ['boolean'],
        ];
    }
}
