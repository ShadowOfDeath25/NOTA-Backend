<?php

namespace App\Http\Requests\User;

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
            "name" => ["string", "max:255"],
            "settings" => ["sometimes", "array"],
            "settings.2FA" => ["sometimes", "boolean"],
            "settings.language" => ["sometimes", 'string', 'in:english,العربية'],
            'settings.email_notifications' => ["sometimes", "boolean"],
            "settings.push_notifications" => ["sometimes", "boolean"],
            "settings.theme" => ["sometimes", 'string', 'in:light,dark']
        ];
    }
}
