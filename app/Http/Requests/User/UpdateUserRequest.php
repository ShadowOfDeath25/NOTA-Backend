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
    public function messages(): array
    {
        return [
            // Name validation (from this file)
           // 'name.string' => 'The name must consist of valid characters.',
            'name.max'    => 'Your name cannot be longer than 255 characters.',

            // Root settings key
           // 'settings.array' => 'The configuration payload must be a valid array structure.',

            // Nested settings rules (using dot notation)
          //  'settings.2FA.boolean'                 => 'The two-factor authentication setting must be enabled or disabled.',
            'settings.language.string'             => 'The selected language format is invalid.',
            'settings.language.in'                 => 'Please select a supported language (English or العربية).',
           // 'settings.email_notifications.boolean' => 'The email notification toggle must be true or false.',
           // 'settings.push_notifications.boolean'  => 'The push notification toggle must be true or false.',
            'settings.theme.string'                => 'The selected theme format is invalid.',
            'settings.theme.in'                    => 'The selected theme must be either "light" or "dark".',
        ];
    }
}
