<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Article $article): JsonResponse
    {
        $currentUser = auth('api')->user();
        $comments = $article->comments()->with('author')->latest()->get();

        return response()->json([
            'comments' => $comments->map(fn ($comment) => $this->formatComment($comment, $currentUser)),
        ]);
    }

    public function store(Request $request, Article $article): JsonResponse
    {
        $request->validate([
            'comment.body' => ['required', 'string'],
        ]);

        $comment = $article->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->input('comment.body'),
        ]);

        $comment->load('author');

        return response()->json([
            'comment' => $this->formatComment($comment, $request->user()),
        ], 201);
    }

    public function destroy(Request $request, Article $article, Comment $comment): JsonResponse
    {
        if ($request->user()->id !== $comment->user_id) {
            abort(403, 'You are not authorized to delete this comment.');
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully']);
    }

    private function formatComment(Comment $comment, $currentUser): array
    {
        return [
            'id' => $comment->id,
            'createdAt' => $comment->created_at->toISOString(),
            'updatedAt' => $comment->updated_at->toISOString(),
            'body' => $comment->body,
            'author' => [
                'username' => $comment->author->username,
                'bio' => $comment->author->bio,
                'image' => $comment->author->image,
                'following' => $currentUser ? $currentUser->isFollowing($comment->author) : false,
            ],
        ];
    }
}
