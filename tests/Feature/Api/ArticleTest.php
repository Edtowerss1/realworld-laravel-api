<?php

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

test('can create an article when authenticated', function () {

    // Create an authenticated user
    $user = User::factory()->create([
        'username' => 'jake',
    ]);
    $token = JWTAuth::fromUser($user);

    // prepare the payload for the article creation
    $payload = [
        'article' => [
            'title' => 'How to train your dragon',
            'description' => 'Ever wonder how?',
            'body' => 'It takes a Jacobian',
            'tagList' => ['dragons', 'training'],
        ],
    ];

    // send a POST request to the articles endpoint
    $response = $this->withHeader('Authorization', "Token {$token}")
        ->postJson('/api/articles', $payload);

    // assert that the response has a 201 status code and correct structure
    $response->assertStatus(201)
        ->assertJsonStructure([
            'article' => [
                'slug',
                'title',
                'description',
                'body',
                'tagList',
                'createdAt',
                'updatedAt',
                'favorited',
                'favoritesCount',
                'author' => [
                    'username',
                    'bio',
                    'image',
                    'following',
                ],
            ],
        ])
        ->assertJson([
            'article' => [
                'title' => 'How to train your dragon',
                'slug' => 'how-to-train-your-dragon', // slug autogenerado a partir del título
                'description' => 'Ever wonder how?',
                'body' => 'It takes a Jacobian',
                'author' => [
                    'username' => 'jake',
                    'following' => false,
                ],
            ],
        ]);
});

test('cannot create an article when unauthenticated', function () {

    // prepare the payload
    $payload = [
        'article' => [
            'title' => 'How to train your dragon',
            'description' => 'Ever wonder how?',
            'body' => 'It takes a Jacobian',
        ],
    ];

    // send POST request without token
    $response = $this->postJson('/api/articles', $payload);

    // assert unauthorized
    $response->assertStatus(401);
});

test('can get an article by slug without authentication', function () {

    // Create an author and an article
    $author = User::factory()->create(['username' => 'jake']);
    $article = Article::factory()->create([
        'user_id' => $author->id,
        'title' => 'My first article',
        'slug' => 'my-first-article',
        'description' => 'Description here',
    ]);

    // send a GET request to the article endpoint
    $response = $this->getJson('/api/articles/my-first-article');

    // assert response is 200 and matches article data
    $response->assertStatus(200)
        ->assertJson([
            'article' => [
                'slug' => 'my-first-article',
                'title' => 'My first article',
                'author' => [
                    'username' => 'jake',
                ],
            ],
        ]);
});

test('can update an article if authenticated user is the author', function () {

    // Create author and article
    $author = User::factory()->create();
    $token = JWTAuth::fromUser($author);

    $article = Article::factory()->create([
        'user_id' => $author->id,
        'title' => 'Original Title',
        'slug' => 'original-title',
    ]);

    // prepare the update payload
    $payload = [
        'article' => [
            'title' => 'Updated Title',
        ],
    ];

    // send PUT request to update
    $response = $this->withHeader('Authorization', "Token {$token}")
        ->putJson('/api/articles/original-title', $payload);

    // assert success and slug updated
    $response->assertStatus(200)
        ->assertJson([
            'article' => [
                'title' => 'Updated Title',
                'slug' => 'updated-title', // El slug debe recalcularse si cambia el título
            ],
        ]);
});

test('cannot update an article if user is not the author', function () {

    // Create author and article
    $author = User::factory()->create();
    $article = Article::factory()->create([
        'user_id' => $author->id,
        'title' => 'Original Title',
        'slug' => 'original-title',
    ]);

    // Create a different user (attacker)
    $otherUser = User::factory()->create();
    $token = JWTAuth::fromUser($otherUser);

    // attempt to update
    $response = $this->withHeader('Authorization', "Token {$token}")
        ->putJson('/api/articles/original-title', [
            'article' => ['title' => 'Hacked Title'],
        ]);

    // assert forbidden (403)
    $response->assertStatus(403);
});

test('can delete an article if authenticated user is the author', function () {

    // Create author and article
    $author = User::factory()->create();
    $token = JWTAuth::fromUser($author);

    $article = Article::factory()->create([
        'user_id' => $author->id,
        'slug' => 'to-be-deleted',
    ]);

    // send DELETE request
    $response = $this->withHeader('Authorization', "Token {$token}")
        ->deleteJson('/api/articles/to-be-deleted');

    // assert 200 OK or 204 No Content (RealWorld uses 200 with an empty body or success message, often 204 is acceptable too)
    $response->assertStatus(200);

    // verify it is deleted from database
    $this->assertDatabaseMissing('articles', ['id' => $article->id]);
});
