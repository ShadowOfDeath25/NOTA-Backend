<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => ['required', 'string', Password::default(), 'confirmed'],

        ];
    }
    public function messages(): array
    {
        return [
            // Name
            'name.required' => 'The name field is required.',
            'name.max'      => 'The name may not be greater than 255 characters.',

            // Email
            'email.required' => 'The email address is required.',
            'email.email'    => 'Please provide a valid email address.',
            'email.max'      => 'The email may not be greater than 255 characters.',
            'email.unique'   => 'This email address is already registered.',

            // Password
            'password.required'  => 'The password field is required.',
            'password.string'    => 'The password must be a valid string.',
            'password.min'       => 'Your password must be at least 8 characters long.',
            'password.letters' => 'Your password must contain letters.',
            // 3. Mixed Case requirement (e.g., ->mixedCase())
            'password.mixed_case' => 'Your password must contain both uppercase and lowercase letters.',
            // 4. Numbers requirement (e.g., ->numbers())
            'password.numbers' => 'Your password must include at least one number.',
            // 5. Symbols / Special Characters requirement (e.g., ->symbols())
            'password.symbols' => 'Your password must include at least one special character (e.g., @, $, !, %).',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }
}
