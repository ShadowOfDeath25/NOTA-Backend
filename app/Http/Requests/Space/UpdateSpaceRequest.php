<?php

namespace App\Http\Requests\Space;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSpaceRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['string', 'sometimes'],
        ];
    }
    public function messages(): array
    {
        return [
            // Name
            'name.required' => 'The name field is required.',
            //'name.string'   => 'The name must be a valid string.',
            'name.max'      => 'The name may not be greater than 255 characters.',

            // Description
          //  'description.string' => 'The description must be a valid string.',
        ];
    }
}
