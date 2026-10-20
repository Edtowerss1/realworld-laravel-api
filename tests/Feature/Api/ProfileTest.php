<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

test('can get user profile without authentication', function () {

    // Create a target user in the database
    User::factory()->create([
        'username' => 'jake',
        'bio' => 'I like to skateboard',
        'image' => 'https://i.stack.imgur.com/xHWG8.jpg',
    ]);

    // send a GET request to the profile endpoint without token
    $response = $this->getJson('/api/profiles/jake');

    // assert that the response status is 200 and following is false by default
    $response->assertStatus(200)
        ->assertJsonStructure([
            'profile' => [
                'username',
                'bio',
                'image',
                'following',
            ],
        ])
        ->assertJson([
            'profile' => [
                'username' => 'jake',
                'bio' => 'I like to skateboard',
                'image' => 'https://i.stack.imgur.com/xHWG8.jpg',
                'following' => false,
            ],
        ]);
});

test('get profile returns correct following status when authenticated', function () {

    // Create authenticated user and target user
    $user = User::factory()->create();
    $targetUser = User::factory()->create(['username' => 'jake']);

    // Make the user follow the target user
    $user->follow($targetUser);

    // Generate token for the user
    $token = JWTAuth::fromUser($user);

    // send a GET request to the profile endpoint with the authorization token
    $response = $this->withHeader('Authorization', "Token {$token}")
        ->getJson('/api/profiles/jake');

    // assert that the profile shows following as true
    $response->assertStatus(200)
        ->assertJson([
            'profile' => [
                'username' => 'jake',
                'following' => true,
            ],
        ]);
});

test('get profile returns 404 when user does not exist', function () {

    // send a GET request for a non-existent username
    $response = $this->getJson('/api/profiles/nonexistentuser');

    // assert that the response status is 404
    $response->assertStatus(404);
});

test('can follow another user profile when authenticated', function () {

    // Create authenticated user and target user
    $user = User::factory()->create();
    $targetUser = User::factory()->create(['username' => 'jake']);

    // Generate token for the user
    $token = JWTAuth::fromUser($user);

    // send a POST request to follow the target user
    $response = $this->withHeader('Authorization', "Token {$token}")
        ->postJson('/api/profiles/jake/follow');

    // assert that response status is 200 and profile following is true
    $response->assertStatus(200)
        ->assertJson([
            'profile' => [
                'username' => 'jake',
                'following' => true,
            ],
        ]);

    // assert that the relationship was created in the database
    expect($user->isFollowing($targetUser))->toBeTrue();
});

test('follow endpoint fails when user is unauthenticated', function () {

    // Create target user
    User::factory()->create(['username' => 'jake']);

    // send a POST request to follow without token
    $response = $this->postJson('/api/profiles/jake/follow');

    // assert that response status is 401
    $response->assertStatus(401);
});

test('can unfollow a previously followed user profile', function () {

    // Create authenticated user and target user
    $user = User::factory()->create();
    $targetUser = User::factory()->create(['username' => 'jake']);

    // Make the user follow the target user initially
    $user->follow($targetUser);

    // Generate token for the user
    $token = JWTAuth::fromUser($user);

    // send a DELETE request to unfollow the target user
    $response = $this->withHeader('Authorization', "Token {$token}")
        ->deleteJson('/api/profiles/jake/follow');

    // assert that response status is 200 and profile following is false
    $response->assertStatus(200)
        ->assertJson([
            'profile' => [
                'username' => 'jake',
                'following' => false,
            ],
        ]);

    // assert that the relationship was removed from the database
    expect($user->isFollowing($targetUser))->toBeFalse();
});

test('unfollow endpoint fails when user is unauthenticated', function () {

    // Create target user
    User::factory()->create(['username' => 'jake']);

    // send a DELETE request to unfollow without token
    $response = $this->deleteJson('/api/profiles/jake/follow');

    // assert that response status is 401
    $response->assertStatus(401);
});
