<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    RateLimiter::clear('login-ip:127.0.0.1');

    $this->user = User::factory()->create([
        'name' => 'Ani',
        'email' => 'ani@example.test',
        'password' => Hash::make('LatihanWeb2!2026'),
    ]);
});

test('session login returns 200 with user data and no-store header for valid credentials', function () {
    $response = $this->postJson('/login', [
        'email' => 'ani@example.test',
        'password' => 'LatihanWeb2!2026',
    ]);

    $response->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJson([
            'data' => [
                'id' => $this->user->id,
                'name' => 'Ani',
            ],
        ]);

    $this->assertAuthenticatedAs($this->user);
});

test('session login returns 401 on invalid credentials', function () {
    $response = $this->postJson('/login', [
        'email' => 'ani@example.test',
        'password' => 'WrongPassword!',
    ]);

    $response->assertStatus(401)
        ->assertJson(['message' => 'Kredensial tidak valid.']);

    $this->assertGuest();
});

test('session login returns 422 on missing fields', function () {
    $response = $this->postJson('/login', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

test('authenticated session can access me endpoint and logout successfully', function () {
    $login = $this->postJson('/login', [
        'email' => 'ani@example.test',
        'password' => 'LatihanWeb2!2026',
    ]);
    $login->assertOk();

    $me = $this->getJson('/api/v1/me');
    $me->assertOk()
        ->assertJson([
            'data' => [
                'id' => $this->user->id,
                'name' => 'Ani',
            ],
        ]);

    $logout = $this->postJson('/logout');
    $logout->assertNoContent();

    $this->assertGuest();
});
