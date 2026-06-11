<?php

namespace App\Http\Requests\Note;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreNoteRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'array'],
            'space_id' => ['nullable', 'uuid', 'exists:spaces,id'],
            'preview' => ['sometimes', 'string'],
        ];
    }
    public function messages(): array
    {
        return [
            // Title
          //  'title.string'   => 'The title must be a valid string.',
            'title.max'      => 'The title may not be greater than 255 characters.',

            // Content
           // 'content.string' => 'The content must be a valid string.',

            // Space ID
            //'space_id.uuid'   => 'The space ID must be a valid UUID.',
            'space_id.exists' => 'The selected space does not exist.',

            // Preview
          //  'preview.string' => 'The preview must be a valid string.',
        ];
    }
}
