<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterStaffRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only admin users can register staff
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:8'],
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
            'name.string' => 'The name must be a valid text.',
            'name.max' => 'The name cannot exceed 255 characters.',
            
            'email.required' => 'The email field is required.',
            'email.string' => 'The email must be a valid text.',
            'email.email' => 'The email must be a valid email address.',
            'email.max' => 'The email cannot exceed 255 characters.',
            'email.unique' => 'The email has already been taken.',
            
            'password.required' => 'The password field is required.',
            'password.string' => 'The password must be a valid text.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
            
            'password_confirmation.required' => 'The password confirmation field is required.',
            'password_confirmation.string' => 'The password confirmation must be a valid text.',
            'password_confirmation.min' => 'The password confirmation must be at least 8 characters.',
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