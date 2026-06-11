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
    public function messages(): array
    {
        return [
            // Name validation
           // 'name.string' => 'The name must consist of valid characters.',
            'name.max'    => 'Your name cannot be longer than 255 characters.',

            // Root settings array
           // 'settings.array' => 'The configuration payload must be a valid array structure.',

            // Nested settings using dot notation (String format with 'in' rule)
            'settings.language.string'           => 'The selected language format is invalid.',
            'settings.language.in'               => 'Please select a supported language (English or العربية).',

            'settings.theme.string'              => 'The selected theme format is invalid.',
            'settings.theme.in'                  => 'The selected theme must be either "light" or "dark".',

            'settings.email_notification.string' => 'The email notification value must be text.',
            'settings.email_notification.in'     => 'The email notification toggle must be set to "on" or "off".',

            'settings.push_notification.string'  => 'The push notification value must be text.',
            'settings.push_notification.in'      => 'The push notification toggle must be set to "on" or "off".',

            'settings.2FA.string'                => 'The two-factor authentication value must be text.',
            'settings.2FA.in'                    => 'The two-factor authentication toggle must be set to "on" or "off".',
        ];
    }
}
