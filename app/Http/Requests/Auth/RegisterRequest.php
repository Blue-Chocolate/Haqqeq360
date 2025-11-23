<?php 

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[a-zA-Z\s\-\']+$/',
            ],
            'second_name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[a-zA-Z\s\-\']+$/',
            ],
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                'unique:users,email',
            ],
            'phone' => [
                'nullable',
                'string',
                'min:10',
                'max:20',
                'regex:/^[0-9\+\-\(\)\s]+$/',
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'role' => [
                'required',
                'string',
                'in:learner,instructor,admin',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'first_name.regex' => 'First name can only contain letters, spaces, hyphens, and apostrophes.',
            'second_name.required' => 'Last name is required.',
            'email.unique' => 'This email address is already registered.',
            'phone.regex' => 'Phone number format is invalid.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.in' => 'Role must be one of: learner, instructor, or admin.',
        ];
    }
}