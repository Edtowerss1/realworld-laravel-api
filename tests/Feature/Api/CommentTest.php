<?php

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

test('can get comments for an article', function () {
    $article = Article::factory()->create();
    Comment::factory()->count(2)->create(['article_id' => $article->id]);

    $response = $this->getJson("/api/articles/{$article->slug}/comments");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'comments' => [
                '*' => ['id', 'createdAt', 'updatedAt', 'body', 'author'],
            ],
        ]);

    expect(count($response->json('comments')))->toBe(2);
});

test('can add a comment to an article when authenticated', function () {
    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);
    $article = Article::factory()->create();

    $payload = [
        'comment' => [
            'body' => 'His name was Hisoka',
        ],
    ];

    $response = $this->withHeader('Authorization', "Token {$token}")
        ->postJson("/api/articles/{$article->slug}/comments", $payload);

    $response->assertStatus(201)
        ->assertJson([
            'comment' => [
                'body' => 'His name was Hisoka',
                'author' => [
                    'username' => $user->username,
                ],
            ],
        ]);
});

test('can delete own comment from an article', function () {
    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);
    $article = Article::factory()->create();
    $comment = Comment::factory()->create([
        'user_id' => $user->id,
        'article_id' => $article->id,
    ]);

    $response = $this->withHeader('Authorization', "Token {$token}")
        ->deleteJson("/api/articles/{$article->slug}/comments/{$comment->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
});
