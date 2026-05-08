<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Jobs\SendPostPublishedEmailJob;
use App\Models\Post;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PostController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum', except: ['index', 'show']),
        ];
    }

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Post::class);

        $posts = Post::published()
            ->with('author')
            ->latest('published_at')
            ->paginate(10);

        return PostResource::collection($posts);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $this->authorize('create', Post::class);

        $post = $request->user()->posts()->create($request->validated());

        if ($post->published_at?->isPast()) {
            SendPostPublishedEmailJob::dispatch($post);
        }

        $post->load('author');

        return PostResource::make($post)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Post $post): PostResource
    {
        $this->authorize('view', $post);

        $post->loadMissing('author');

        return PostResource::make($post);
    }

    public function update(UpdatePostRequest $request, Post $post): PostResource
    {
        $this->authorize('update', $post);

        $post->update($request->validated());
        $post->loadMissing('author');

        return PostResource::make($post);
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->authorize('delete', $post);

        $post->delete();

        return response()->json([
            'message' => 'Post deleted.',
        ], 200);
    }
}
