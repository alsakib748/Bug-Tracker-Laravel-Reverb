<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
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
            'comment' => 'required|string|min:2|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => 'Comment cannot be empty.',
            'comment.min' => 'Comment must be at least 2 characters.',
            'comment.max' => 'Comment cannot exceed 1000 characters.',
        ];
    }

}
