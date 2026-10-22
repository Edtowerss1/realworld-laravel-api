<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('register returns 201 with the user payload and a usable token', function () {
    $payload = [
        'user' => [
            'username' => 'Jacob',
            'email' => 'jake@jake.jake',
            'password' => 'jakejake',
        ],
    ];

    $response = $this->postJson('/api/users', $payload);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'user' => ['email', 'token', 'username', 'bio', 'image'],
        ])
        ->assertJson([
            'user' => [
                'email' => 'jake@jake.jake',
                'username' => 'Jacob',
                'bio' => null,
                'image' => null,
            ],
        ]);

    expect($response->json('user.token'))->toBeString()->not->toBeEmpty();
    $response->assertJsonMissingPath('user.password');
});

test('the token returned by register can access a protected endpoint', function () {
    $token = $this->postJson('/api/users', [
        'user' => [
            'username' => 'Jacob',
            'email' => 'jake@jake.jake',
            'password' => 'jakejake',
        ],
    ])->json('user.token');

    $this->withHeader('Authorization', "Token {$token}")
        ->getJson('/api/user')
        ->assertStatus(200)
        ->assertJsonPath('user.email', 'jake@jake.jake');
});

test('register persists the user and stores the password hashed', function () {
    $this->postJson('/api/users', [
        'user' => [
            'username' => 'Jacob',
            'email' => 'jake@jake.jake',
            'password' => 'jakejake',
        ],
    ])->assertStatus(201);

    $this->assertDatabaseHas('users', [
        'email' => 'jake@jake.jake',
        'username' => 'Jacob',
    ]);

    $user = User::where('email', 'jake@jake.jake')->firstOrFail();

    expect($user->password)->not->toBe('jakejake');
    expect(Hash::check('jakejake', $user->password))->toBeTrue();
});

test('register fails when a required field is missing or invalid', function (array $user, string $field) {
    $this->postJson('/api/users', ['user' => $user])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => [$field]]);
})->with([
    'empty username' => [['username' => '', 'email' => 'jake@jake.jake', 'password' => 'jakejake'], 'username'],
    'empty email' => [['username' => 'Jacob', 'email' => '', 'password' => 'jakejake'], 'email'],
    'invalid email' => [['username' => 'Jacob', 'email' => 'not-an-email', 'password' => 'jakejake'], 'email'],
    'empty password' => [['username' => 'Jacob', 'email' => 'jake@jake.jake', 'password' => ''], 'password'],
]);

test('register fails when the email is already taken', function () {
    User::factory()->create(['email' => 'jake@jake.jake']);

    $this->postJson('/api/users', [
        'user' => [
            'username' => 'Another',
            'email' => 'jake@jake.jake',
            'password' => 'jakejake',
        ],
    ])->assertStatus(409)->assertJsonValidationErrors('email');
});

test('register fails when the username is already taken', function () {
    User::factory()->create(['username' => 'Jacob']);

    $this->postJson('/api/users', [
        'user' => [
            'username' => 'Jacob',
            'email' => 'new@jake.jake',
            'password' => 'jakejake',
        ],
    ])->assertStatus(409)->assertJsonValidationErrors('username');
});

test('register fails when the user key is missing from the request body', function () {
    $this->postJson('/api/users', [])
        ->assertStatus(422)
        ->assertJsonStructure(['errors']);
});

test('register validation messages follow the RealWorld format', function () {
    $this->postJson('/api/users', [
        'user' => ['username' => '', 'email' => '', 'password' => ''],
    ])->assertStatus(422)->assertJson([
        'errors' => [
            'username' => ["can't be blank"],
            'email' => ["can't be blank"],
            'password' => ["can't be blank"],
        ],
    ]);
});
