<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreArticleRequest;
use App\Http\Requests\Api\V1\UpdateArticleRequest;
use App\Models\Article;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Iniciamos la consulta ordenando por los más recientes
        $query = Article::with(['author', 'tags'])->latest();

        // Filtro por etiqueta (tag)
        if ($request->has('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('name', $request->tag);
            });
        }

        // Filtro por autor
        if ($request->has('author')) {
            $query->whereHas('author', function ($q) use ($request) {
                $q->where('username', $request->author);
            });
        }

        // Filtro por usuario que marcó como favorito
        if ($request->has('favorited')) {
            $query->whereHas('favoritedBy', function ($q) use ($request) {
                $q->where('username', $request->favorited);
            });
        }

        // Obtenemos el conteo total antes de aplicar limit/offset
        $articlesCount = $query->count();

        // Aplicamos paginación (limit por defecto 20, offset por defecto 0)
        $articles = $query->offset($request->input('offset', 0))
            ->limit($request->input('limit', 20))
            ->get();

        return $this->articleCollectionResponse($articles, $articlesCount);
    }

    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();

        // Obtenemos los IDs de los usuarios a los que sigo
        // Usamos 'users.id' para evitar ambigüedades en la tabla pivote
        $followingIds = $user->following()->pluck('users.id');

        $query = Article::with(['author', 'tags'])
            ->whereIn('user_id', $followingIds)
            ->latest();

        $articlesCount = $query->count();

        $articles = $query->offset($request->input('offset', 0))
            ->limit($request->input('limit', 20))
            ->get();

        return $this->articleCollectionResponse($articles, $articlesCount);
    }

    public function store(StoreArticleRequest $request): JsonResponse
    {
        $user = $request->user();

        // 1. Crear el artículo
        $slug = Str::slug($request->title);
        $baseSlug = $slug;
        $suffix = 1;
        while (Article::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        $article = $user->articles()->create([
            'title' => $request->title,
            'slug' => $slug,
            'description' => $request->description,
            'body' => $request->body,
        ]);

        // 2. Sincronizar etiquetas si vienen en el request
        if ($request->has('tagList')) {
            $tagIds = collect($request->tagList)->mapWithKeys(function ($tagName, $position) {
                return [Tag::firstOrCreate(['name' => $tagName])->id => ['position' => $position]];
            });
            $article->tags()->sync($tagIds);
        }

        // Importante: Cargamos las relaciones para no hacer consultas extra en el formateo
        $article->load(['author', 'tags']);

        return $this->articleResponse($article, 201);
    }

    public function show(Article $article): JsonResponse
    {
        // Cargamos relaciones (eager loading)
        $article->load(['author', 'tags']);

        return $this->articleResponse($article);
    }

    public function update(UpdateArticleRequest $request, Article $article): JsonResponse
    {
        if ($request->user()->id !== $article->user_id) {
            abort(403, 'You are not authorized to edit this article.');
        }

        $data = $request->only(['title', 'description', 'body']);
        if ($request->has('title')) {
            $slug = Str::slug($request->title);
            $baseSlug = $slug;
            $suffix = 1;
            while (Article::where('slug', $slug)
                ->whereKeyNot($article->getKey())
                ->exists()) {
                $slug = $baseSlug.'-'.$suffix++;
            }
            $data['slug'] = $slug;
        }

        $updatedAt = now();
        if ($article->updated_at
            && $updatedAt->format('Y-m-d H:i:s') <= $article->updated_at->format('Y-m-d H:i:s')) {
            $updatedAt = $article->updated_at->copy()->addSecond();
        }
        $data['updated_at'] = $updatedAt;

        $article->fill($data);
        $article->timestamps = false;
        $article->save();
        $article->timestamps = true;
        DB::table('articles')
            ->where('id', $article->getKey())
            ->update(['updated_at' => $updatedAt->format('Y-m-d H:i:s')]);
        $article->refresh();

        if ($request->has('tagList')) {
            $tagIds = collect($request->tagList)->mapWithKeys(function ($tagName, $position) {
                return [Tag::firstOrCreate(['name' => $tagName])->id => ['position' => $position]];
            });
            $article->tags()->sync($tagIds);
        }

        $article->load(['author', 'tags']);

        return $this->articleResponse($article);
    }

    public function destroy(Article $article): Response
    {
        if (request()->user()->id !== $article->user_id) {
            abort(403, 'You are not authorized to delete this article.');
        }

        $article->delete();

        return response()->noContent();
    }

    public function favorite(Request $request, Article $article): JsonResponse
    {
        $request->user()->favorites()->syncWithoutDetaching([$article->id]);

        $article->load(['author', 'tags', 'favoritedBy']);

        return $this->articleResponse($article);
    }

    public function unfavorite(Request $request, Article $article): JsonResponse
    {
        $request->user()->favorites()->detach($article->id);

        $article->load(['author', 'tags', 'favoritedBy']);

        return $this->articleResponse($article);
    }

    private function formatArticle(Article $article, ?User $currentUser, bool $includeBody = true): array
    {
        $isFollowing = $currentUser ? $currentUser->isFollowing($article->author) : false;

        $formatted = [
            'slug' => $article->slug,
            'title' => $article->title,
            'description' => $article->description,
            'tagList' => $article->tags->pluck('name'),
            'createdAt' => $article->created_at->toISOString(),
            'updatedAt' => $article->updated_at->toISOString(),
            'favorited' => $article->isFavoritedBy($currentUser),
            'favoritesCount' => $article->favoritedBy->count(),
            'author' => [
                'username' => $article->author->username,
                'bio' => $article->author->bio,
                'image' => $article->author->image,
                'following' => $isFollowing,
            ],
        ];

        if ($includeBody) {
            $formatted['body'] = $article->body;
        }

        return $formatted;
    }

    /**
     * Respuesta JSON para UN artículo.
     * (Reemplaza tu antiguo método articleResponse por este)
     */
    private function articleResponse(Article $article, int $status = 200): JsonResponse
    {
        return response()->json([
            'article' => $this->formatArticle($article, auth('api')->user()),
        ], $status);
    }

    /**
     * Respuesta JSON para COLECCIONES de artículos.
     */
    private function articleCollectionResponse($articles, int $count): JsonResponse
    {
        $currentUser = auth('api')->user();

        return response()->json([
            // Mapeamos la colección para formatear cada artículo
            'articles' => $articles->map(fn ($article) => $this->formatArticle($article, $currentUser, false)),
            'articlesCount' => $count,
        ]);
    }
}
