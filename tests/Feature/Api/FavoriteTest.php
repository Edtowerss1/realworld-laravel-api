<?php

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

test('can favorite an article when authenticated', function () {
    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);
    $article = Article::factory()->create();

    $response = $this->withHeader('Authorization', "Token {$token}")
        ->postJson("/api/articles/{$article->slug}/favorite");

    $response->assertStatus(200)
        ->assertJson([
            'article' => [
                'slug' => $article->slug,
                'favorited' => true,
                'favoritesCount' => 1,
            ],
        ]);

    expect($article->isFavoritedBy($user))->toBeTrue();
});

test('can unfavorite a previously favorited article', function () {
    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);
    $article = Article::factory()->create();

    // El usuario marca el artículo como favorito previamente
    $user->favorites()->attach($article->id);

    $response = $this->withHeader('Authorization', "Token {$token}")
        ->deleteJson("/api/articles/{$article->slug}/favorite");

    $response->assertStatus(200)
        ->assertJson([
            'article' => [
                'slug' => $article->slug,
                'favorited' => false,
                'favoritesCount' => 0,
            ],
        ]);

    expect($article->isFavoritedBy($user))->toBeFalse();
});

test('favorite endpoints require authentication', function () {
    $article = Article::factory()->create();

    $this->postJson("/api/articles/{$article->slug}/favorite")->assertStatus(401);
    $this->deleteJson("/api/articles/{$article->slug}/favorite")->assertStatus(401);
});

test('can filter articles list by favorited username', function () {
    $user = User::factory()->create(['username' => 'johndoe']);
    $article1 = Article::factory()->create();
    $article2 = Article::factory()->create();

    // Solo $article1 es favorito de $user
    $user->favorites()->attach($article1->id);

    $response = $this->getJson('/api/articles?favorited=johndoe');

    $response->assertStatus(200);
    expect(count($response->json('articles')))->toBe(1);
    expect($response->json('articles.0.slug'))->toBe($article1->slug);
});
