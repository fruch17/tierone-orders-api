<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Registration is public, so always return true
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * Following Input Validation best practices (Security)
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'company_email' => ['required', 'string', 'email', 'max:255', 'unique:clients,company_email'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * Get custom messages for validator errors.
     * Improves user experience with clear error messages
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'company_name.required' => 'The company name field is required.',
            'company_email.required' => 'The company email field is required.',
            'company_email.email' => 'Please provide a valid company email address.',
            'company_email.unique' => 'This company email is already registered.',
            'email.required' => 'The email field is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'The password field is required.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The password must be at least 8 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     * Ensures data is properly formatted before validation
     */
    protected function prepareForValidation(): void
    {
        // Ensure email is lowercase
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower($this->email)
            ]);
        }
    }
}
