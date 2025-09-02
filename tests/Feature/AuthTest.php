<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

pest()->extend(Tests\TestCase::class);
beforeEach(function (): void {
    $this->artisan('migrate:fresh');
});

it('registers a new user successfully', function (): void {
    $data = [
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'phone' => '+1234567890',
        'password' => 'password123',
        'role' => 'passenger',
    ];

    $response = $this->postJson('/api/v1/register', $data);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'phone', 'role'],
            'token',
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'john.doe@example.com',
        'role' => 'passenger',
    ]);
});

it('fails to register with invalid data', function (): void {
    $data = [
        'name' => '',
        'email' => 'invalid-email',
        'phone' => '',
        'password' => 'short',
        'role' => 'invalid_role',
    ];

    $response = $this->postJson('/api/v1/register', $data);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => [
                'name',
                'email',
                'phone',
                'password',
                'role',
            ],
        ]);
});

it('logs in a user successfully', function (): void {
    $user = User::factory()->create([
        'email' => 'john.doe@example.com',
        'password' => Hash::make('password123'),
    ]);

    $data = [
        'email' => 'john.doe@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->postJson('/api/v1/login', $data);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'phone', 'role'],
            'token',
        ]);
});

it('fails to login with invalid credentials', function (): void {
    $user = User::factory()->create([
        'email' => 'john.doe@example.com',
        'password' => Hash::make('password123'),
    ]);

    $data = [
        'email' => 'john.doe@example.com',
        'password' => 'wrongpassword',
        'password_confirmation' => 'wrongpassword',
    ];

    $response = $this->postJson('/api/v1/login', $data);

    $response->assertStatus(401)
        ->assertJson([
            'error' => 'Unauthorized',
        ]);
});

it('logs out an authenticated user successfully', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/logout');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Logged out',
        ]);

    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'tokenable_type' => get_class($user),
    ]);
});

it('fails to logout an unauthenticated user', function (): void {
    $response = $this->postJson('/api/v1/logout');

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});
