<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Jobs\SendPostPublishedEmailJob;
use App\Models\Post;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class PostController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    /**
     * Require authentication for all actions except public reads.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth', except: ['index', 'show']),
        ];
    }

    /**
     * Posts authored by the current user, grouped by state.
     */
    public function mine(Request $request): View
    {
        $user = $request->user();

        return view('posts.mine', [
            'published' => $user->posts()->published()->latest('published_at')->get(),
            'drafts'    => $user->posts()->draft()->latest('updated_at')->get(),
            'scheduled' => $user->posts()->scheduled()->orderBy('published_at')->get(),
        ]);
    }

    /**
     * Public list of published posts.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Post::class);

        $posts = Post::published()
            ->with('author')
            ->latest('published_at')
            ->paginate(10);

        return view('posts.index', compact('posts'));
    }

    public function create(): View
    {
        $this->authorize('create', Post::class);

        return view('posts.create');
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $this->authorize('create', Post::class);

        $post = $request->user()->posts()->create($request->validated());

        if ($post->published_at?->isPast()) {
            SendPostPublishedEmailJob::dispatch($post);
        }

        return redirect()
            ->route('posts.show', $post)
            ->with('status', 'Post created.');
    }

    public function show(Post $post): View
    {
        $this->authorize('view', $post);

        $post->loadMissing('author');

        return view('posts.show', compact('post'));
    }

    public function edit(Post $post): View
    {
        $this->authorize('update', $post);

        return view('posts.edit', compact('post'));
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $this->authorize('update', $post);

        $post->update($request->validated());

        return redirect()
            ->route('posts.show', $post)
            ->with('status', 'Post updated.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        $post->delete();

        return redirect()
            ->route('posts.index')
            ->with('status', 'Post deleted.');
    }
}
