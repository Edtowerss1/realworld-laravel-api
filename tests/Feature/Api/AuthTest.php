<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the authentication endpoint works correctly and returns 200 with user data and token', function () {

    // Create a user in the database
    $user = User::factory()->create([
        'email' => 'jake@jake.jake',
        'password' => bcrypt('jakejake'),
        'username' => 'Jacob',
        'bio' => 'I like to skateboard',
        'image' => 'https://i.stack.imgur.com/xHWG8.jpg',
    ]);

    // prepare the payload for the login request
    $payload = [
        'user' => [
            'email' => 'jake@jake.jake',
            'password' => 'jakejake',
        ],
    ];

    // send a POST request to the login endpoint
    $response = $this->postJson('/api/users/login', $payload);

    // assert that the response has a 200 status code and contains the user data
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
                'email' => 'jake@jake.jake',
                'username' => 'Jacob',
                'bio' => 'I like to skateboard',
                'image' => 'https://i.stack.imgur.com/xHWG8.jpg',
            ],
        ]);

    // assert that the token is not empty and the password is not exposed
    expect($response->json('user.token'))->toBeString()->not->toBeEmpty();
    $response->assertJsonMissingPath('user.password');
});

test('the token returned by the login endpoint can be used to access a protected endpoint', function () {

    // Create a user in the database
    User::factory()->create([
        'email' => 'jake@jake.jake',
        'password' => bcrypt('jakejake'),
    ]);

    // prepare the payload for the login request
    $payload = [
        'user' => [
            'email' => 'jake@jake.jake',
            'password' => 'jakejake',
        ],
    ];

    // send a POST request to the login endpoint and get the token
    $token = $this->postJson('/api/users/login', $payload)->json('user.token');

    // send a GET request to a protected endpoint using the token
    $response = $this->withHeader('Authorization', "Token {$token}")
        ->getJson('/api/user');

    // assert that the response has a 200 status code and returns the authenticated user
    $response->assertStatus(200)
        ->assertJsonPath('user.email', 'jake@jake.jake');
});

test('login fails when a required field is missing or invalid', function (array $user, string $field) {

    // prepare the payload with the missing or invalid field
    $payload = [
        'user' => $user,
    ];

    // send a POST request to the login endpoint
    $response = $this->postJson('/api/users/login', $payload);

    // assert that the response has a 422 status code and contains a validation error for the field
    $response->assertStatus(422)
        ->assertJsonStructure([
            'errors' => [
                $field,
            ],
        ]);
})->with([
    'empty email' => [['email' => '', 'password' => 'secret123'], 'email'],
    'empty password' => [['email' => 'jake@jake.jake', 'password' => ''], 'password'],
    'invalid email' => [['email' => 'not-an-email', 'password' => 'secret123'], 'email'],
]);

test('login fails when the user key is missing from the request body', function () {

    // prepare an empty payload
    $payload = [];

    // send a POST request to the login endpoint
    $response = $this->postJson('/api/users/login', $payload);

    // assert that the response has a 422 status code and contains validation errors
    $response->assertStatus(422)
        ->assertJsonStructure([
            'errors',
        ]);
});

test('login fails with invalid credentials', function () {

    // Create a user in the database
    User::factory()->create([
        'email' => 'jake@jake.jake',
        'password' => bcrypt('correct-password'),
    ]);

    // prepare the payload with incorrect password
    $payload = [
        'user' => [
            'email' => 'jake@jake.jake',
            'password' => 'wrong-password',
        ],
    ];

    // send a POST request to the login endpoint
    $response = $this->postJson('/api/users/login', $payload);

    // assert that the response has a 422 status code and contains an error message
    $response->assertStatus(422)
        ->assertJson([
            'errors' => [
                'email' => ['is invalid'],
            ],
        ]);
});

test('login fails with a non-existent email', function () {

    // prepare the payload with an email that does not exist in the database
    $payload = [
        'user' => [
            'email' => 'nobody@example.com',
            'password' => 'whatever',
        ],
    ];

    // send a POST request to the login endpoint
    $response = $this->postJson('/api/users/login', $payload);

    // assert that the response has a 422 status code and the same error as invalid credentials
    $response->assertStatus(422)
        ->assertJson([
            'errors' => [
                'email' => ['is invalid'],
            ],
        ]);
});
