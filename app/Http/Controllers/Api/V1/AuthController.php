<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\JWTGuard;

class AuthController extends Controller
{
    /**
     * Handle a login request.
     */
    public function login(LoginRequest $request, JWTGuard $guard): JsonResponse
    {
        // attempt() verifies the credentials and returns a JWT, or false if they are invalid
        $token = $guard->attempt($request->only('email', 'password'));

        // Same error for a non-existent email and a wrong password,
        // so we do not reveal which emails are registered
        if (!$token) {
            throw ValidationException::withMessages([
                'email' => ['is invalid'],
            ]);
        }

        return $this->userResponse($guard->user(), $token)->setStatusCode(200);
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

    public function register(RegisterRequest $request, JWTGuard $guard): JsonResponse
    {
        $user = User::create($request->only('username', 'email', 'password'));

        $token = $guard->fromUser($user);

        return $this->userResponse($user, $token)->setStatusCode(201);

    }

    public function update(UpdateUserRequest $request, JWTGuard $guard): JsonResponse
    {
        $user = $request->user();

        // Update the user with the validated data
        $user->update($request->validated());

        // If the password was updated, we need to generate a new token
        if ($request->filled('password')) {
            $token = $guard->fromUser($user);
        } else {
            $token = $request->bearerToken();
        }

        return $this->userResponse($user, $token)->setStatusCode(200);
    }
}
