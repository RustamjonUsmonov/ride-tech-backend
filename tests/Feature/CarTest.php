<?php

declare(strict_types=1);

use App\Models\Car;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

pest()->extend(Tests\TestCase::class);

beforeEach(function (): void {
    $this->artisan('migrate:fresh');
});

it('lists cars for authenticated user', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789',
        'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    Car::create([
        'user_id' => $user->id,
        'model' => 'Model S',
        'brand' => 'Tesla',
        'license_plate' => 'ABC123',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/cars');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'model', 'brand', 'license_plate', 'created_at', 'updated_at'],
            ],
            'links',
            'meta',
        ])
        ->assertJsonCount(1, 'data');
});

it('fails to list cars for unauthenticated user', function (): void {
    $response = $this->getJson('/api/v1/cars');

    $response->assertStatus(401)
        ->assertJson(['message' => 'Unauthenticated.']);
});

it('stores a new car for authenticated user', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789',
        'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    Sanctum::actingAs($user);

    $data = [
        'model' => 'Model 3',
        'brand' => 'Tesla',
        'license_plate' => 'XYZ789',
    ];

    $response = $this->postJson('/api/v1/cars', $data);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'id',
            'model',
            'brand',
            'license_plate',
            'created_at',
            'updated_at',
        ])
        ->assertJsonFragment(['license_plate' => 'XYZ789']);

    $this->assertDatabaseHas('cars', [
        'user_id' => $user->id,
        'license_plate' => 'XYZ789',
    ]);

    expect(Cache::has("user_{$user->id}_cars"))->toBeFalse();
});

it('fails to store car with invalid data', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789',
        'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    Sanctum::actingAs($user);

    $data = [
        'model' => '',
        'brand' => '',
        'license_plate' => '',
    ];

    $response = $this->postJson('/api/v1/cars', $data);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['model', 'brand', 'license_plate'],
        ]);
});

it('fails to store car for unauthenticated user', function (): void {
    $data = [
        'model' => 'Model 3',
        'brand' => 'Tesla',
        'license_plate' => 'XYZ789',
    ];

    $response = $this->postJson('/api/v1/cars', $data);

    $response->assertStatus(401)
        ->assertJson(['message' => 'Unauthenticated.']);
});

it('deletes a car for authorized user', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789',
        'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    $car = Car::create([
        'user_id' => $user->id,
        'model' => 'Model S',
        'brand' => 'Tesla',
        'license_plate' => 'ABC123',
    ]);

    Sanctum::actingAs($user);

    $response = $this->deleteJson("/api/v1/cars/{$car->id}");

    $response->assertStatus(200)
        ->assertJson(['message' => 'Car deleted']);

    $this->assertDatabaseMissing('cars', [
        'id' => $car->id,
        'user_id' => $user->id,
    ]);

    expect(Cache::has("user_{$user->id}_cars"))->toBeFalse();
});

it('fails to delete car for unauthenticated user', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789',
        'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    $car = Car::create([
        'user_id' => $user->id,
        'model' => 'Model S',
        'brand' => 'Tesla',
        'license_plate' => 'ABC123',
    ]);

    $response = $this->deleteJson("/api/v1/cars/{$car->id}");

    $response->assertStatus(401)
        ->assertJson(['message' => 'Unauthenticated.']);
});

it('fails to delete non-existent car', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789',
        'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    Sanctum::actingAs($user);

    $response = $this->deleteJson('/api/v1/cars/999');

    $response->assertStatus(404)
        ->assertJsonStructure(['message']);
});

it('fails to delete car for unauthorized user', function (): void {
    $user1 = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789',
        'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    $user2 = User::create([
        'name' => 'Jane Doe',
        'phone' => '+987654321',
        'email' => 'jane.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    $car = Car::create([
        'user_id' => $user1->id,
        'model' => 'Model S',
        'brand' => 'Tesla',
        'license_plate' => 'ABC123',
    ]);

    Sanctum::actingAs($user2);

    $response = $this->deleteJson("/api/v1/cars/{$car->id}");

    $response->assertStatus(403)
        ->assertJson(['message' => 'This action is unauthorized.']);
});
