<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('issues a token on valid login', function () {
    User::factory()->create([
        'email'    => 'api-test@example.com',
        'password' => 'password',
    ]);

    $response = $this->postJson('/api/login', [
        'email'       => 'api-test@example.com',
        'password'    => 'password',
        'device_name' => 'pest-test',
    ]);

    $response
        ->assertOk()
        ->assertJsonStructure([
            'user'  => ['id', 'name', 'email'],
            'token',
        ])
        ->assertJsonPath('user.email', 'api-test@example.com');

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
});

it('rejects invalid credentials with 422 and a clean error', function () {
    User::factory()->create([
        'email'    => 'api-test@example.com',
        'password' => 'password',
    ]);

    $this->postJson('/api/login', [
        'email'       => 'api-test@example.com',
        'password'    => 'wrong-password',
        'device_name' => 'pest-test',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('requires device_name on login', function () {
    User::factory()->create([
        'email'    => 'api-test@example.com',
        'password' => 'password',
    ]);

    $this->postJson('/api/login', [
        'email'    => 'api-test@example.com',
        'password' => 'password',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('device_name');
});

it('returns the authenticated user via /api/user when given a valid token', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('email', $user->email);
});

it('rejects /api/user without a token with 401', function () {
    $this->getJson('/api/user')->assertStatus(401);
});

it('logs out by revoking the current access token', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('pest-test')->plainTextToken;

    expect($user->tokens()->count())->toBe(1);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Logged out.');

    expect($user->fresh()->tokens()->count())->toBe(0);
});

it('rejects logout without a token with 401', function () {
    $this->postJson('/api/logout')->assertStatus(401);
});
