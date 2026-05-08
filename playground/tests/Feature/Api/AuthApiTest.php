<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

it('issues a sanctum token on successful login', function () {
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => bcrypt('secret-pwd'),
    ]);

    $response = postJson('/api/login', [
        'email'       => 'login@example.com',
        'password'    => 'secret-pwd',
        'device_name' => 'phpunit-device',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
            'token',
        ])
        ->assertJsonPath('user.id', $user->id);

    expect($user->fresh()->tokens()->where('name', 'phpunit-device')->count())->toBe(1);
});

it('rejects login with wrong password', function () {
    User::factory()->create([
        'email' => 'login@example.com',
        'password' => bcrypt('secret-pwd'),
    ]);

    postJson('/api/login', [
        'email'       => 'login@example.com',
        'password'    => 'wrong-pwd',
        'device_name' => 'phpunit-device',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('requires device_name on login', function () {
    User::factory()->create([
        'email' => 'login@example.com',
        'password' => bcrypt('secret-pwd'),
    ]);

    postJson('/api/login', [
        'email'    => 'login@example.com',
        'password' => 'secret-pwd',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['device_name']);
});

it('returns the authenticated user from /api/me', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.name', $user->name);
});

it('rejects /api/me without a token with 401 json', function () {
    getJson('/api/me')
        ->assertStatus(401)
        ->assertExactJson(['message' => 'Unauthenticated.']);
});

it('revokes only the current token on logout', function () {
    $user = User::factory()->create();
    $tokenA = $user->createToken('device-a')->plainTextToken;
    $user->createToken('device-b');

    expect($user->tokens()->count())->toBe(2);

    postJson('/api/logout', [], [
        'Authorization' => "Bearer $tokenA",
    ])->assertOk();

    expect($user->fresh()->tokens()->count())->toBe(1)
        ->and($user->fresh()->tokens()->first()->name)->toBe('device-b');
});
