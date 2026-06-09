<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'settings' => ['sometimes', 'array'],
            'settings.language' => ['sometimes', 'string', 'in:english,العربية'],
            'settings.theme' => ['sometimes', 'string', 'in:dark,light'],
            'settings.email_notification' => ['sometimes', 'string', 'in:on,off'],
            'settings.push_notification' => ['sometimes', 'string', 'in:on,off'],
            'settings.2FA' => ['sometimes', 'string', 'in:on,off'],
        ];
    }
}
