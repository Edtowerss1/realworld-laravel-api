<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

test('the update endpoint works correctly and returns 200 with updated user data', function () {

    // Create a user in the database
    $user = User::factory()->create([
        'email' => 'jake@jake.jake',
        'password' => bcrypt('jakejake'),
        'username' => 'Jacob',
        'bio' => 'old bio',
        'image' => 'https://old.image.jpg',
    ]);

    // Generate a token for the user
    $token = JWTAuth::fromUser($user);

    // prepare the payload for the update request
    $payload = [
        'user' => [
            'email' => 'newjake@jake.jake',
            'username' => 'NewJacob',
            'password' => 'newpassword',
            'bio' => 'I like to skateboard',
            'image' => 'https://i.stack.imgur.com/xHWG8.jpg',
        ],
    ];

    // send a PUT request to the update endpoint using the token
    $response = $this->withHeader('Authorization', "Token {$token}")
        ->putJson('/api/user', $payload);

    // assert that the response has a 200 status code and contains the updated user data
    $response->assertStatus(200)
        ->assertJsonStructure([
            'user' => [
                'email',
                'token',
                'username',
                'bio',
                'image',
            ],
        ])
        ->assertJson([
            'user' => [
                'email' => 'newjake@jake.jake',
                'username' => 'NewJacob',
                'bio' => 'I like to skateboard',
                'image' => 'https://i.stack.imgur.com/xHWG8.jpg',
            ],
        ]);

    // assert that the password was actually hashed and updated in the database
    $user->refresh();
    expect(Hash::check('newpassword', $user->password))->toBeTrue();
});

test('the update endpoint allows partial updates without affecting other fields', function () {

    // Create a user in the database
    $user = User::factory()->create([
        'email' => 'jake@jake.jake',
        'username' => 'Jacob',
        'bio' => 'I like to skateboard',
        'image' => 'https://i.stack.imgur.com/xHWG8.jpg',
    ]);

    // Generate a token for the user
    $token = JWTAuth::fromUser($user);

    // prepare the payload with only the bio and image fields
    $payload = [
        'user' => [
            'bio' => 'I like to surf now',
            'image' => 'https://new.image.jpg',
        ],
    ];

    // send a PUT request to the update endpoint
    $response = $this->withHeader('Authorization', "Token {$token}")
        ->putJson('/api/user', $payload);

    // assert that the response has a 200 status code and only updated the requested fields
    $response->assertStatus(200)
        ->assertJson([
            'user' => [
                'email' => 'jake@jake.jake',
                'username' => 'Jacob',
                'bio' => 'I like to surf now',
                'image' => 'https://new.image.jpg',
            ],
        ]);
});

test('update fails when the user is not authenticated', function () {

    // prepare the payload for the update request
    $payload = [
        'user' => [
            'bio' => 'I like to surf',
        ],
    ];

    // send a PUT request without the Authorization header
    $response = $this->putJson('/api/user', $payload);

    // assert that the response has a 401 status code
    $response->assertStatus(401);
});

test('update allows submitting the current email and username without unique validation errors', function () {

    // Create a user in the database
    $user = User::factory()->create([
        'email' => 'jake@jake.jake',
        'username' => 'Jacob',
    ]);

    // Generate a token for the user
    $token = JWTAuth::fromUser($user);

    // prepare the payload with the exact same email and username
    $payload = [
        'user' => [
            'email' => 'jake@jake.jake',
            'username' => 'Jacob',
            'bio' => 'Updated bio',
        ],
    ];

    // send a PUT request to the update endpoint
    $response = $this->withHeader('Authorization', "Token {$token}")
        ->putJson('/api/user', $payload);

    // assert that the response has a 200 status code (no validation errors)
    $response->assertStatus(200)
        ->assertJsonPath('user.bio', 'Updated bio');
});

test('update fails when the email or username is already taken by another user', function () {

    // Create another user in the database to occupy the email and username
    User::factory()->create([
        'email' => 'taken@example.com',
        'username' => 'TakenUsername',
    ]);

    // Create the acting user
    $user = User::factory()->create([
        'email' => 'jake@jake.jake',
        'username' => 'Jacob',
    ]);

    // Generate a token for the acting user
    $token = JWTAuth::fromUser($user);

    // prepare the payload attempting to use the already taken credentials
    $payload = [
        'user' => [
            'email' => 'taken@example.com',
            'username' => 'TakenUsername',
        ],
    ];

    // send a PUT request to the update endpoint
    $response = $this->withHeader('Authorization', "Token {$token}")
        ->putJson('/api/user', $payload);

    // assert that the response has a 409 status code and contains validation errors
    $response->assertStatus(409)
        ->assertJson([
            'errors' => [
                'email' => ['has already been taken'],
                'username' => ['has already been taken'],
            ],
        ]);
});
