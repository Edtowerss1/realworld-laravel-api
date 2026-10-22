<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
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
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
    * Error messages in the RealWorld style ("can't be blank", "is invalid")
     */
    public function messages(): array
    {
        return [
            'email.required' => "can't be blank",
            'email.email' => 'is invalid',
            'password.required' => "can't be blank",
            'password.string' => 'is invalid',
        ];
    }
}
