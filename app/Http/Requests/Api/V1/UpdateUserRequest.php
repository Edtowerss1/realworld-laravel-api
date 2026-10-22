<?php

namespace App\Http\Requests\Api\V1;

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
        $userId = $this->user()->id;

        return [
            'username' => ['sometimes', 'string', 'unique:users,username,'.$userId],
            'email' => ['sometimes', 'email', 'unique:users,email,'.$userId],
            'password' => ['sometimes', 'string', 'min:8'],
            'bio' => ['sometimes', 'nullable', 'string'],
            'image' => ['sometimes', 'nullable', 'url'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.string' => "can't be empty",
            'username.unique' => 'has already been taken',
            'email.email' => 'is invalid',
            'email.unique' => 'has already been taken',
            'password.string' => 'is invalid',
            'password.min' => 'is invalid',
            'bio.string' => 'is invalid',
            'image.url' => 'is invalid',
        ];
    }
}
