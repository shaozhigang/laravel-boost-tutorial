<?php

use App\Models\Post;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
|--------------------------------------------------------------------------
| Filament panel access control (FilamentUser::canAccessPanel)
|--------------------------------------------------------------------------
*/

it('redirects guests to the admin login page', function () {
    get('/admin')
        ->assertRedirect('/admin/login');
});

it('lets an admin user reach the admin dashboard', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    actingAs($admin)
        ->get('/admin')
        ->assertOk();
});

it('forbids a non-admin user from reaching the admin dashboard', function () {
    $user = User::factory()->create(['is_admin' => false]);

    actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| PostPolicy admin bypass (before() hook)
|--------------------------------------------------------------------------
*/

it('allows an admin to update any post (PostPolicy::before bypass)', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $other = User::factory()->create();
    $post = Post::factory()->for($other, 'author')->create();

    expect($admin->can('update', $post))->toBeTrue()
        ->and($admin->can('delete', $post))->toBeTrue()
        ->and($admin->can('view', $post))->toBeTrue();
});

it('still forbids a non-admin from updating someone else\'s post', function () {
    $randomUser = User::factory()->create(['is_admin' => false]);
    $author = User::factory()->create();
    $post = Post::factory()->for($author, 'author')->create();

    expect($randomUser->can('update', $post))->toBeFalse()
        ->and($randomUser->can('delete', $post))->toBeFalse();
});
