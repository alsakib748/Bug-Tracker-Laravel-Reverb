<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadAttachmentRequest extends FormRequest
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
            'file' => 'required|file|max:20480|mimes:jpg,jpeg,png,gif,webp,pdf,txt,log,csv,zip', // 20 MB max
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded file is not valid.',
            'file.max' => 'The file size must not exceed 20 MB.',
            'file.mimes' => 'The file type is not allowed. Allowed types: jpg, jpeg, png, gif, webp, pdf, txt, log, csv, zip.',
        ];
    }

}