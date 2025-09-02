<?php

declare(strict_types=1);

use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

pest()->extend(Tests\TestCase::class);

beforeEach(function (): void {
    $this->artisan('migrate:fresh');
});

it('lists reviews for a specific driver', function (): void {
    $driver = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789', 'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    $passenger = User::create([
        'name' => 'Jane Smith',
        'phone' => '+123456783', 'email' => 'jane.smith@example.com',
        'password' => bcrypt('password123'),
        'role' => 'passenger',
    ]);

    Review::create([
        'driver_id' => $driver->id,
        'passenger_id' => $passenger->id,
        'rating' => 5,
        'comment' => 'Great ride!',
    ]);

    Sanctum::actingAs($passenger);

    $response = $this->getJson("/api/v1/reviews/{$driver->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'driver' => ['id', 'name'],
                    'passenger' => ['id', 'name'],
                    'rating',
                    'comment',
                    'created_at',
                    'updated_at',
                ],
            ],
        ])
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['rating' => 5, 'comment' => 'Great ride!']);
});

it('fails to list reviews for unauthenticated user', function (): void {
    $driver = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789', 'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    $response = $this->getJson("/api/v1/reviews/{$driver->id}");

    $response->assertStatus(401)
        ->assertJson(['message' => 'Unauthenticated.']);
});

it('stores a review for a specific driver', function (): void {
    $driver = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789', 'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    $passenger = User::create([
        'name' => 'Jane Smith',
        'phone' => '+123456783', 'email' => 'jane.smith@example.com',
        'password' => bcrypt('password123'),
        'role' => 'passenger',
    ]);

    App\Models\Trip::create([
        'driver_id' => $driver->id,
        'passenger_id' => $passenger->id,
        'start_address' => 'Point A',
        'end_address' => 'Point B',
        'status' => 'completed',
    ]);
    Sanctum::actingAs($passenger);

    $data = [
        'rating' => 4,
        'comment' => 'Smooth ride!',
    ];

    $response = $this->postJson("/api/v1/reviews/{$driver->id}", $data);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'id',
            'rating',
            'comment',
            'created_at',
            'updated_at',
        ])
        ->assertJsonFragment(['rating' => 4, 'comment' => 'Smooth ride!']);

    $this->assertDatabaseHas('reviews', [
        'driver_id' => $driver->id,
        'passenger_id' => $passenger->id,
        'rating' => 4,
        'comment' => 'Smooth ride!',
    ]);

    expect(Cache::has("driver_{$driver->id}_reviews"))->toBeFalse();
});

it('fails to store review with invalid data', function (): void {
    $driver = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789', 'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    $passenger = User::create([
        'name' => 'Jane Smith',
        'phone' => '+123456783', 'email' => 'jane.smith@example.com',
        'password' => bcrypt('password123'),
        'role' => 'passenger',
    ]);

    Sanctum::actingAs($passenger);

    $data = [
        'rating' => 6,
        'comment' => '',
    ];

    $response = $this->postJson("/api/v1/reviews/{$driver->id}", $data);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['rating'],
        ]);
});

it('fails to store review for unauthenticated user', function (): void {
    $driver = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789', 'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    $data = [
        'rating' => 4,
        'comment' => 'Smooth ride!',
    ];

    $response = $this->postJson("/api/v1/reviews/{$driver->id}", $data);

    $response->assertStatus(401)
        ->assertJson(['message' => 'Unauthenticated.']);
});

it('fails to store review for Unauthenticated. user', function (): void {
    $driver = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789', 'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    $passenger = User::create([
        'name' => 'Jane Smith',
        'phone' => '+123456783', 'email' => 'jane.smith@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver', // only passengers can review
    ]);

    Sanctum::actingAs($passenger);

    $data = [
        'rating' => 4,
        'comment' => 'Smooth ride!',
    ];

    $response = $this->postJson("/api/v1/reviews/{$driver->id}", $data);

    $response->assertStatus(403)
        ->assertJson(['message' => 'This action is unauthorized.']);
});
