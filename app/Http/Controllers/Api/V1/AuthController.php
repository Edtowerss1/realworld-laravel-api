<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle a login request.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // attempt() verifies the credentials and returns a JWT, or false if they are invalid
        $token = auth('api')->attempt($request->only('email', 'password'));

        // Same error for a non-existent email and a wrong password,
        // so we do not reveal which emails are registered
        if (!$token) {
            throw ValidationException::withMessages([
                'email' => ['is invalid'],
            ]);
        }

        return $this->userResponse(auth('api')->user(), $token);
    }

    public function current(Request $request): JsonResponse
    {
        return $this->userResponse($request->user(), $request->bearerToken());
    }

    private function userResponse(User $user, string $token): JsonResponse
    {
        return response()->json([
            'user' => [
                'email' => $user->email,
                'token' => $token,
                'username' => $user->username,
                'bio' => $user->bio,
                'image' => $user->image,
            ],
        ]);
    }
}
