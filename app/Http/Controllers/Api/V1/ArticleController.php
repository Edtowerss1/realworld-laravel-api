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
        $article = $user->articles()->create([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'description' => $request->description,
            'body' => $request->body,
        ]);

        // 2. Sincronizar etiquetas si vienen en el request
        if ($request->has('tagList')) {
            $tagIds = collect($request->tagList)->map(function ($tagName) {
                return Tag::firstOrCreate(['name' => $tagName])->id;
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
            $data['slug'] = Str::slug($request->title);
        }

        $article->update($data);
        $article->load(['author', 'tags']);

        return $this->articleResponse($article);
    }

    public function destroy(Article $article): JsonResponse
    {
        if (request()->user()->id !== $article->user_id) {
            abort(403, 'You are not authorized to delete this article.');
        }

        $article->delete();

        return response()->json(['message' => 'Article deleted successfully']);
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

    private function formatArticle(Article $article, ?User $currentUser): array
    {
        $isFollowing = $currentUser ? $currentUser->isFollowing($article->author) : false;

        return [
            'slug' => $article->slug,
            'title' => $article->title,
            'description' => $article->description,
            'body' => $article->body,
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
            'articles' => $articles->map(fn ($article) => $this->formatArticle($article, $currentUser)),
            'articlesCount' => $count,
        ]);
    }
}
