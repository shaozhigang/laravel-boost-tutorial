<?php

use App\Models\Post;
use App\Models\User;

it('returns JSON 401 even without Accept header on protected api routes', function () {
    $response = $this->post('/api/posts', [
        'title' => 'Test',
        'slug'  => 'test',
        'body'  => 'Body',
    ]);

    $response->assertStatus(401);
    expect($response->headers->get('Content-Type'))->toContain('application/json');
});

it('returns JSON 404 even without Accept header for non-existent api resources', function () {
    $response = $this->get('/api/posts/this-slug-does-not-exist');

    $response->assertStatus(404);
    expect($response->headers->get('Content-Type'))->toContain('application/json');
});

it('returns JSON 422 even without Accept header for validation errors', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->post('/api/posts', []);

    $response->assertStatus(422);
    expect($response->headers->get('Content-Type'))->toContain('application/json');
});

it('returns JSON 403 even without Accept header for policy denials', function () {
    $author = User::factory()->create();
    $other  = User::factory()->create();
    $post   = Post::factory()->for($author, 'author')->create();

    $token = $other->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->delete("/api/posts/{$post->slug}");

    $response->assertStatus(403);
    expect($response->headers->get('Content-Type'))->toContain('application/json');
});
