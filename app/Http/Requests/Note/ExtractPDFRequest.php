<?php

namespace App\Http\Requests\Note;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ExtractPDFRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'extensions:pdf', 'mimes:pdf'],
            'space_id' => ['sometimes', 'exists:spaces,id'],
        ];
    }
    public function messages(): array
    {
        return [
            // Custom messages for the 'file' field
            'file.required'   => 'Please select a file to upload.',
            'file.file'       => 'The uploaded item must be a valid file.',
            'file.extensions' => 'Only PDF files are allowed based on the file extension.',
            'file.mimes'      => 'The file must be a valid PDF document type.',

            // Custom messages for the 'space_id' field
          //  'space_id.sometimes' => 'The space ID field is optional but must be valid if provided.',
            'space_id.exists'    => 'The selected space does not exist.',
        ];
    }
}
