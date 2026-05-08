<?php

use App\Models\Post;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('lists published posts publicly with pagination', function () {
    $author = User::factory()->create();
    Post::factory()->count(3)->for($author, 'author')->create();
    Post::factory()->draft()->for($author, 'author')->create();

    $response = $this->getJson('/api/posts');

    $response
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                ['id', 'slug', 'title', 'body', 'published_at', 'author' => ['id', 'name']],
            ],
            'links',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonCount(3, 'data');
});

it('does not leak drafts or scheduled posts in the public api index', function () {
    $author = User::factory()->create();
    Post::factory()->count(2)->for($author, 'author')->create();
    Post::factory()->draft()->for($author, 'author')->create();
    Post::factory()->scheduled()->for($author, 'author')->create();

    $this->getJson('/api/posts')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('shows a single published post by slug', function () {
    $author = User::factory()->create();
    $post   = Post::factory()->for($author, 'author')->create(['slug' => 'hello-world']);

    $this->getJson('/api/posts/hello-world')
        ->assertOk()
        ->assertJsonPath('data.slug', 'hello-world')
        ->assertJsonPath('data.author.id', $author->id)
        ->assertJsonPath('data.author.name', $author->name);
});

it('rejects guest reading a draft post via api with 403', function () {
    $author = User::factory()->create();
    $draft  = Post::factory()->draft()->for($author, 'author')->create();

    $this->getJson("/api/posts/{$draft->slug}")
        ->assertForbidden();
});

it('rejects unauthenticated post creation with 401', function () {
    $this->postJson('/api/posts', [
        'title' => 'X',
        'slug'  => 'x',
        'body'  => '...',
    ])->assertStatus(401);
});

it('lets an authenticated user create a post via api and returns 201', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/posts', [
        'title'        => 'My API Post',
        'slug'         => 'my-api-post',
        'body'         => 'Created via API',
        'published_at' => now()->toIso8601String(),
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.slug', 'my-api-post')
        ->assertJsonPath('data.author.id', $user->id);

    expect(Post::where('slug', 'my-api-post')->first()->user_id)->toBe($user->id);
});

it('forces the token holder as the author even if user_id is forged', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/posts', [
        'title'   => 'Hijack Attempt',
        'slug'    => 'hijack-via-api',
        'body'    => 'Trying to forge author through API',
        'user_id' => $other->id,
    ])->assertCreated();

    $post = Post::where('slug', 'hijack-via-api')->firstOrFail();
    expect($post->user_id)->toBe($user->id);
});

it('validates api store input and returns 422 with errors', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/posts', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'slug', 'body']);
});

it('lets the author update their own post via api', function () {
    $author = User::factory()->create();
    $post   = Post::factory()->for($author, 'author')->create(['slug' => 'old-slug']);

    Sanctum::actingAs($author);

    $this->putJson("/api/posts/old-slug", [
        'title'        => 'Updated Title',
        'slug'         => 'old-slug',
        'body'         => 'Updated body',
        'published_at' => $post->published_at?->toIso8601String(),
    ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Updated Title');

    expect($post->fresh()->title)->toBe('Updated Title');
});

it('forbids non-authors from updating via api with 403', function () {
    $author = User::factory()->create();
    $other  = User::factory()->create();
    $post   = Post::factory()->for($author, 'author')->create();

    Sanctum::actingAs($other);

    $this->putJson("/api/posts/{$post->slug}", [
        'title'        => 'Hijack',
        'slug'         => $post->slug,
        'body'         => 'Trying to hijack',
        'published_at' => $post->published_at?->toIso8601String(),
    ])->assertForbidden();
});

it('lets the author delete their own post via api with 204', function () {
    $author = User::factory()->create();
    $post   = Post::factory()->for($author, 'author')->create();

    Sanctum::actingAs($author);

    $this->deleteJson("/api/posts/{$post->slug}")
        ->assertNoContent();

    expect(Post::find($post->id))->toBeNull();
});

it('forbids non-authors from deleting via api with 403', function () {
    $author = User::factory()->create();
    $other  = User::factory()->create();
    $post   = Post::factory()->for($author, 'author')->create();

    Sanctum::actingAs($other);

    $this->deleteJson("/api/posts/{$post->slug}")
        ->assertForbidden();

    expect(Post::find($post->id))->not->toBeNull();
});
