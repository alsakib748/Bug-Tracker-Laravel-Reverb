<?php

namespace App\Http\Requests\GitHub;

use Illuminate\Foundation\Http\FormRequest;

class StoreGitHubLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('issue')) ?? false;
    }

    public function rules(): array
    {
        return [
            'github_pull_request_id' => ['required', 'integer', 'exists:github_pull_requests,id'],
        ];
    }
}
