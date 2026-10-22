<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(string $username)
    {
        $targetUser = User::where('username', $username)->firstOrFail();

        $currentUser = auth('api')->user();

        return $this->profileResponse($targetUser, $currentUser);

    }

    private function profileResponse(User $targetUser, ?User $currentUser)
    {

        return response()->json([
            'profile' => [
                'username' => $targetUser->username,
                'bio' => $targetUser->bio,
                'image' => $targetUser->image,
                'following' => $currentUser ? $currentUser->isFollowing($targetUser) : false,
            ],
        ]);
    }

    public function follow(Request $request, string $username): JsonResponse
    {
        $targetUser = User::where('username', $username)->firstOrFail();
        $currentUser = $request->user();

        $currentUser->follow($targetUser);

        return $this->profileResponse($targetUser, $currentUser);
    }

    public function unfollow(Request $request, string $username): JsonResponse
    {
        $targetUser = User::where('username', $username)->firstOrFail();
        $currentUser = $request->user();

        $currentUser->unfollow($targetUser);

        return $this->profileResponse($targetUser, $currentUser);
    }
}
