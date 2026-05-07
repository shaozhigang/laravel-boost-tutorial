<?php

use App\Models\Post;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;

/*
|--------------------------------------------------------------------------
| Auth middleware (write actions require login)
|--------------------------------------------------------------------------
*/

it('redirects guests from write actions', function (string $verb, string $path) {
    $response = match ($verb) {
        'GET'    => get($path),
        'POST'   => post($path, []),
        'PATCH'  => patch($path, []),
        'DELETE' => delete($path),
    };

    $response->assertRedirect(route('login'));
})->with([
    'create page' => ['GET',    '/posts/create'],
    'store'       => ['POST',   '/posts'],
    'edit page'   => ['GET',    '/posts/any-slug/edit'],
    'update'      => ['PATCH',  '/posts/any-slug'],
    'destroy'     => ['DELETE', '/posts/any-slug'],
]);

/*
|--------------------------------------------------------------------------
| Store
|--------------------------------------------------------------------------
*/

it('lets an authenticated user create a post', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('posts.store'), [
            'title'        => 'Hello World',
            'slug'         => 'hello-world',
            'body'         => 'First post body.',
            'published_at' => now()->toDateTimeString(),
        ])
        ->assertRedirect(route('posts.show', 'hello-world'));

    expect(Post::where('slug', 'hello-world')->first())
        ->not->toBeNull()
        ->title->toBe('Hello World')
        ->user_id->toBe($user->id);
});

it('forces the authenticated user as the author, ignoring any submitted user_id', function () {
    $user      = User::factory()->create();
    $other     = User::factory()->create();

    actingAs($user)->post(route('posts.store'), [
        'title'   => 'Hijack Attempt',
        'slug'    => 'hijack-attempt',
        'body'    => '...',
        'user_id' => $other->id, // attacker tries to forge author
    ]);

    $post = Post::where('slug', 'hijack-attempt')->firstOrFail();

    expect($post->user_id)->toBe($user->id);
});

it('validates store input', function (array $payload, string $field) {
    Post::factory()->create(['slug' => 'taken-slug']); // for uniqueness check

    actingAs(User::factory()->create())
        ->post(route('posts.store'), $payload)
        ->assertInvalid($field);
})->with([
    'title required'   => [['title' => '',                          'slug' => 'a', 'body' => 'b'], 'title'],
    'title max 200'    => [['title' => str_repeat('a', 201),        'slug' => 'a', 'body' => 'b'], 'title'],
    'slug required'    => [['title' => 't',                         'slug' => '',  'body' => 'b'], 'slug'],
    'slug unique'      => [['title' => 't',                         'slug' => 'taken-slug', 'body' => 'b'], 'slug'],
    'body required'    => [['title' => 't',                         'slug' => 's', 'body' => ''], 'body'],
    'published_at date'=> [['title' => 't', 'slug' => 's', 'body' => 'b', 'published_at' => 'not-a-date'], 'published_at'],
]);

it('accepts a null published_at to create a draft', function () {
    actingAs(User::factory()->create())
        ->post(route('posts.store'), [
            'title'        => 'Draft Post',
            'slug'         => 'draft-post',
            'body'         => '...',
            'published_at' => null,
        ])
        ->assertRedirect();

    expect(Post::where('slug', 'draft-post')->first()->published_at)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Update + Policy
|--------------------------------------------------------------------------
*/

it('lets the author update their own post', function () {
    $author = User::factory()->create();
    $post   = Post::factory()->for($author, 'author')->create();

    actingAs($author)
        ->patch(route('posts.update', $post), [
            'title'        => 'Updated Title',
            'slug'         => $post->slug,
            'body'         => 'Updated body.',
            'published_at' => $post->published_at?->toDateTimeString(),
        ])
        ->assertRedirect(route('posts.show', $post->slug));

    expect($post->fresh())
        ->title->toBe('Updated Title')
        ->body->toBe('Updated body.');
});

it('forbids non-authors from updating someone else\'s post', function () {
    $author = User::factory()->create();
    $other  = User::factory()->create();
    $post   = Post::factory()->for($author, 'author')->create(['title' => 'Original']);

    actingAs($other)
        ->patch(route('posts.update', $post), [
            'title' => 'Hacked',
            'slug'  => $post->slug,
            'body'  => 'Hacked body',
        ])
        ->assertForbidden();

    expect($post->fresh()->title)->toBe('Original');
});

it('lets the author keep the same slug on update (unique ignores self)', function () {
    $author = User::factory()->create();
    $post   = Post::factory()->for($author, 'author')->create(['slug' => 'my-slug']);

    actingAs($author)
        ->patch(route('posts.update', $post), [
            'title' => 'New Title',
            'slug'  => 'my-slug', // same slug — must NOT trigger unique error
            'body'  => 'New body',
        ])
        ->assertRedirect();

    expect($post->fresh()->title)->toBe('New Title');
});

/*
|--------------------------------------------------------------------------
| Destroy + Policy
|--------------------------------------------------------------------------
*/

it('lets the author delete their own post', function () {
    $author = User::factory()->create();
    $post   = Post::factory()->for($author, 'author')->create();

    actingAs($author)
        ->delete(route('posts.destroy', $post))
        ->assertRedirect(route('posts.index'));

    expect(Post::find($post->id))->toBeNull();
});

it('forbids non-authors from deleting someone else\'s post', function () {
    $author = User::factory()->create();
    $other  = User::factory()->create();
    $post   = Post::factory()->for($author, 'author')->create();

    actingAs($other)
        ->delete(route('posts.destroy', $post))
        ->assertForbidden();

    expect(Post::find($post->id))->not->toBeNull();
});
