<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

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
     * The payload comes wrapped in a "user" key, so we move its content
     * to the root. This way the validation errors are returned as
     * "email" and "password" instead of "user.email" and "user.password".
     */
    protected function prepareForValidation(): void
    {
        $this->merge($this->input('user', []));
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'unique:users,username'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Error messages in the RealWorld style ("can't be empty", "is invalid")
     */
    public function messages(): array
    {
        return [
            'username.required' => "can't be empty",
            'username.string' => 'is invalid',
            'email.required' => "can't be empty",
            'email.email' => 'is invalid',
            'password.required' => "can't be empty",
            'password.string' => 'is invalid',
            'username.unique' => 'has already been taken',
            'email.unique' => 'has already been taken',
        ];
    }
}
