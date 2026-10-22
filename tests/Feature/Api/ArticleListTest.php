<?php

use App\Models\Article;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

test('can get global articles list with default pagination', function () {
    Article::factory()->count(25)->create();

    $response = $this->getJson('/api/articles');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'articles' => [
                '*' => ['slug', 'title', 'author']
            ],
            'articlesCount'
        ]);

    // RealWorld exige un límite por defecto de 20
    expect(count($response->json('articles')))->toBe(20);
    expect($response->json('articlesCount'))->toBe(25);
});

test('can filter articles by author username', function () {
    $author1 = User::factory()->create(['username' => 'jake']);
    $author2 = User::factory()->create(['username' => 'ana']);

    Article::factory()->count(2)->create(['user_id' => $author1->id]);
    Article::factory()->count(3)->create(['user_id' => $author2->id]);

    $response = $this->getJson('/api/articles?author=jake');

    $response->assertStatus(200);
    expect(count($response->json('articles')))->toBe(2);
    expect($response->json('articlesCount'))->toBe(2);
});

test('can filter articles by tag', function () {
    $tag = Tag::factory()->create(['name' => 'laravel']);

    // Creamos 5 artículos, pero solo a 2 les asignamos el tag
    $articlesWithTag = Article::factory()->count(2)->create();
    foreach ($articlesWithTag as $article) {
        $article->tags()->attach($tag->id);
    }

    Article::factory()->count(3)->create(); // Artículos sin tag

    $response = $this->getJson('/api/articles?tag=laravel');

    $response->assertStatus(200);
    expect(count($response->json('articles')))->toBe(2);
    expect($response->json('articlesCount'))->toBe(2);
});

test('can get personalized feed with only followed authors articles', function () {
    $currentUser = User::factory()->create();
    $token = JWTAuth::fromUser($currentUser);

    $followedUser = User::factory()->create();
    $unfollowedUser = User::factory()->create();

    $currentUser->follow($followedUser);

    // 2 artículos del usuario que sigo, 3 del que no sigo
    Article::factory()->count(2)->create(['user_id' => $followedUser->id]);
    Article::factory()->count(3)->create(['user_id' => $unfollowedUser->id]);

    $response = $this->withHeader('Authorization', "Token {$token}")
        ->getJson('/api/articles/feed');

    $response->assertStatus(200);

    // Solo debe devolver los 2 artículos del autor seguido
    expect(count($response->json('articles')))->toBe(2);
    expect($response->json('articlesCount'))->toBe(2);
});

test('feed endpoint fails when unauthenticated', function () {
    $response = $this->getJson('/api/articles/feed');
    $response->assertStatus(401);
});
