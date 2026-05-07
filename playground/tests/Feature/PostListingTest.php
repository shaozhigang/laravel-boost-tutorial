<?php

use App\Models\Post;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
|--------------------------------------------------------------------------
| GET /posts (public index)
|--------------------------------------------------------------------------
*/

it('shows published posts on the public index', function () {
    $published = Post::factory()->create(['title' => 'Visible Post']);

    get('/posts')
        ->assertOk()
        ->assertSee('Visible Post');
});

it('does not leak drafts or scheduled posts on the public index', function () {
    Post::factory()->draft()->create(['title' => 'Secret Draft']);
    Post::factory()->scheduled()->create(['title' => 'Secret Scheduled']);

    get('/posts')
        ->assertOk()
        ->assertDontSee('Secret Draft')
        ->assertDontSee('Secret Scheduled');
});

it('allows guests to view the public index', function () {
    get('/posts')->assertOk();
});

/*
|--------------------------------------------------------------------------
| GET /posts/{slug} (show)
|--------------------------------------------------------------------------
*/

it('resolves the show route by slug, not id', function () {
    $post = Post::factory()->create(['slug' => 'hello-world']);

    get("/posts/hello-world")
        ->assertOk()
        ->assertSee($post->title);
});

it('lets guests read a published post', function () {
    $post = Post::factory()->create();

    get(route('posts.show', $post))
        ->assertOk()
        ->assertSee($post->title);
});

it('forbids guests from reading a draft', function () {
    $post = Post::factory()->draft()->create();

    get(route('posts.show', $post))->assertForbidden();
});

it('forbids guests from reading a scheduled post', function () {
    $post = Post::factory()->scheduled()->create();

    get(route('posts.show', $post))->assertForbidden();
});

it('lets the author preview their own draft', function () {
    $author = User::factory()->create();
    $post   = Post::factory()->draft()->for($author, 'author')->create();

    actingAs($author)
        ->get(route('posts.show', $post))
        ->assertOk()
        ->assertSee($post->title);
});

it('forbids other users from reading someone else\'s draft', function () {
    $author    = User::factory()->create();
    $other     = User::factory()->create();
    $draftPost = Post::factory()->draft()->for($author, 'author')->create();

    actingAs($other)
        ->get(route('posts.show', $draftPost))
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| GET /my/posts (author dashboard)
|--------------------------------------------------------------------------
*/

it('redirects guests away from /my/posts', function () {
    get('/my/posts')->assertRedirect(route('login'));
});

it('shows the current user\'s posts in all three states', function () {
    $user = User::factory()->create();
    Post::factory()->for($user, 'author')->create(['title' => 'My Published']);
    Post::factory()->draft()->for($user, 'author')->create(['title' => 'My Draft']);
    Post::factory()->scheduled()->for($user, 'author')->create(['title' => 'My Scheduled']);

    actingAs($user)
        ->get('/my/posts')
        ->assertOk()
        ->assertSee('My Published')
        ->assertSee('My Draft')
        ->assertSee('My Scheduled');
});

it('does not show other users posts on /my/posts', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();
    Post::factory()->for($other, 'author')->create(['title' => 'Other Published']);
    Post::factory()->draft()->for($other, 'author')->create(['title' => 'Other Draft']);

    actingAs($user)
        ->get('/my/posts')
        ->assertOk()
        ->assertDontSee('Other Published')
        ->assertDontSee('Other Draft');
});
