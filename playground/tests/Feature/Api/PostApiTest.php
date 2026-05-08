<?php

use App\Jobs\SendPostPublishedEmailJob;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

/*
|--------------------------------------------------------------------------
| GET /api/posts (list)
|--------------------------------------------------------------------------
*/

it('lists published posts as a paginated json collection', function () {
    Post::factory()->count(3)->create();
    Post::factory()->draft()->create();

    getJson('/api/posts')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'slug', 'body', 'published_at', 'is_published', 'author', 'created_at', 'updated_at'],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta'  => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonCount(3, 'data');
});

it('omits draft and scheduled posts from the public list', function () {
    Post::factory()->create(['title' => 'Published one']);
    Post::factory()->draft()->create(['title' => 'Hidden draft']);
    Post::factory()->scheduled()->create(['title' => 'Hidden scheduled']);

    $response = getJson('/api/posts')->assertOk();

    $titles = collect($response->json('data'))->pluck('title');
    expect($titles)->toContain('Published one')
        ->not->toContain('Hidden draft')
        ->not->toContain('Hidden scheduled');
});

/*
|--------------------------------------------------------------------------
| GET /api/posts/{slug} (show)
|--------------------------------------------------------------------------
*/

it('shows a single published post by slug as a wrapped json resource', function () {
    $post = Post::factory()->create(['slug' => 'hello-api']);

    getJson('/api/posts/hello-api')
        ->assertOk()
        ->assertJsonPath('data.id', $post->id)
        ->assertJsonPath('data.slug', 'hello-api')
        ->assertJsonPath('data.is_published', true)
        ->assertJsonPath('data.author.id', $post->user_id);
});

it('exposes only id and name on the embedded author (no email leak)', function () {
    $post = Post::factory()->create();

    $response = getJson("/api/posts/{$post->slug}")->assertOk();

    expect($response->json('data.author'))
        ->toHaveKeys(['id', 'name'])
        ->not->toHaveKey('email')
        ->not->toHaveKey('password');
});

it('returns a json 404 for a missing post slug', function () {
    getJson('/api/posts/does-not-exist')
        ->assertStatus(404)
        ->assertJsonStructure(['message']);
});

/*
|--------------------------------------------------------------------------
| POST /api/posts (store)
|--------------------------------------------------------------------------
*/

it('rejects unauthenticated post creation with 401 json', function () {
    postJson('/api/posts', [
        'title' => 'X', 'slug' => 'x', 'body' => 'X', 'published_at' => null,
    ])->assertStatus(401)
        ->assertExactJson(['message' => 'Unauthenticated.']);
});

it('lets an authenticated user create a post via api and returns 201 with the resource', function () {
    Queue::fake();
    $author = User::factory()->create();
    Sanctum::actingAs($author);

    $response = postJson('/api/posts', [
        'title' => 'API Post',
        'slug'  => 'api-post',
        'body'  => 'Created via api.',
        'published_at' => now()->subHour()->toIso8601String(),
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.slug', 'api-post')
        ->assertJsonPath('data.author.id', $author->id);

    Queue::assertPushed(SendPostPublishedEmailJob::class);
});

it('returns 422 with validation errors on invalid post payload', function () {
    Sanctum::actingAs(User::factory()->create());

    postJson('/api/posts', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'slug', 'body']);
});

/*
|--------------------------------------------------------------------------
| PUT /api/posts/{slug} (update)
|--------------------------------------------------------------------------
*/

it('lets the author update their own post via api', function () {
    $author = User::factory()->create();
    $post = Post::factory()->for($author, 'author')->create();
    Sanctum::actingAs($author);

    putJson("/api/posts/{$post->slug}", [
        'title' => 'Updated title',
        'slug'  => $post->slug,
        'body'  => 'Updated body.',
        'published_at' => $post->published_at?->toIso8601String(),
    ])->assertOk()
        ->assertJsonPath('data.title', 'Updated title');

    expect($post->fresh()->title)->toBe('Updated title');
});

it('forbids non-authors from updating via api with 403 json', function () {
    $author = User::factory()->create();
    $post = Post::factory()->for($author, 'author')->create();

    Sanctum::actingAs(User::factory()->create());

    putJson("/api/posts/{$post->slug}", [
        'title' => 'Hijacked',
        'slug'  => $post->slug,
        'body'  => 'Hijacked.',
        'published_at' => $post->published_at?->toIso8601String(),
    ])->assertStatus(403)
        ->assertJsonStructure(['message']);
});

/*
|--------------------------------------------------------------------------
| DELETE /api/posts/{slug} (destroy)
|--------------------------------------------------------------------------
*/

it('lets the author delete their own post via api', function () {
    $author = User::factory()->create();
    $post = Post::factory()->for($author, 'author')->create();
    Sanctum::actingAs($author);

    deleteJson("/api/posts/{$post->slug}")
        ->assertOk()
        ->assertJson(['message' => 'Post deleted.']);

    expect(Post::find($post->id))->toBeNull();
});

it('forbids non-authors from deleting via api with 403', function () {
    $author = User::factory()->create();
    $post = Post::factory()->for($author, 'author')->create();

    Sanctum::actingAs(User::factory()->create());

    deleteJson("/api/posts/{$post->slug}")
        ->assertStatus(403);

    expect(Post::find($post->id))->not->toBeNull();
});
