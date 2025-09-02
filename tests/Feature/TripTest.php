<?php

declare(strict_types=1);

use App\Enums\TripStatusEnum;
use App\Models\Car;
use App\Models\Trip;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

pest()->extend(Tests\TestCase::class);

beforeEach(function (): void {
    $this->artisan('migrate:fresh');
});

it('lists trips for authenticated passenger', function (): void {
    $passenger = User::create([
        'name' => 'Jane Smith',
        'phone' => '+123456783', 'email' => 'jane.smith@example.com',
        'password' => bcrypt('password123'),
        'role' => 'passenger',
    ]);

    $driver = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789', 'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    $car = Car::create([
        'user_id' => $driver->id,
        'model' => 'Toyota Prius',
        'brand' => 'Toyota',
        'license_plate' => 'ABC123',
    ]);

    Trip::create([
        'passenger_id' => $passenger->id,
        'driver_id' => $driver->id,
        'start_address' => '123 Main St',
        'end_address' => '456 Elm St',
        'status' => TripStatusEnum::COMPLETED->value,
    ]);

    Sanctum::actingAs($passenger);

    $response = $this->getJson('/api/v1/trips');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'passenger' => ['id', 'name'],
                    'driver' => ['id', 'name'],
                    'start_address',
                    'end_address',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ],
        ])
        ->assertJsonCount(1, 'data');
});

it('lists trips with status filter', function (): void {
    $passenger = User::create([
        'name' => 'Jane Smith',
        'phone' => '+123456783', 'email' => 'jane.smith@example.com',
        'password' => bcrypt('password123'),
        'role' => 'passenger',
    ]);

    $driver = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789', 'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    $car = Car::create([
        'user_id' => $driver->id,
        'model' => 'Toyota Prius',
        'brand' => 'Toyota',
        'license_plate' => 'ABC123',
    ]);

    Trip::create([
        'passenger_id' => $passenger->id,
        'driver_id' => $driver->id,
        'car_id' => $car->id,
        'start_address' => '123 Main St',
        'end_address' => '456 Elm St',
        'status' => TripStatusEnum::COMPLETED->value,
    ]);

    Trip::create([
        'passenger_id' => $passenger->id,
        'driver_id' => $driver->id,
        'car_id' => $car->id,
        'start_address' => '789 Oak St',
        'end_address' => '101 Pine St',
        'status' => TripStatusEnum::PENDING->value,
    ]);

    Sanctum::actingAs($passenger);

    $response = $this->getJson('/api/v1/trips?status=completed');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['status' => TripStatusEnum::COMPLETED->value]);
});

it('lists trips with date filter', function (): void {
    $passenger = User::create([
        'name' => 'Jane Smith',
        'phone' => '+123456783', 'email' => 'jane.smith@example.com',
        'password' => bcrypt('password123'),
        'role' => 'passenger',
    ]);

    $driver = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789', 'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    $car = Car::create([
        'user_id' => $driver->id,
        'model' => 'Toyota Prius',
        'brand' => 'Toyota',
        'license_plate' => 'ABC123',
    ]);

    Trip::create([
        'passenger_id' => $passenger->id,
        'driver_id' => $driver->id,
        'start_address' => '123 Main St',
        'end_address' => '456 Elm St',
        'status' => TripStatusEnum::COMPLETED->value,
        'created_at' => now(),
    ]);

    Sanctum::actingAs($passenger);

    $response = $this->getJson('/api/v1/trips?date='.now()->format('Y-m-d'));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['start_address' => '123 Main St']);
});

it('fails to list trips for unauthenticated user', function (): void {
    $response = $this->getJson('/api/v1/trips');

    $response->assertStatus(401)
        ->assertJson(['message' => 'Unauthenticated.']);
});

it('stores a new trip for authenticated passenger', function (): void {
    $passenger = User::create([
        'name' => 'Jane Smith',
        'phone' => '+123456783', 'email' => 'jane.smith@example.com',
        'password' => bcrypt('password123'),
        'role' => 'passenger',
    ]);

    $driver = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789', 'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    $car = Car::create([
        'user_id' => $driver->id,
        'model' => 'Toyota Prius',
        'brand' => 'Toyota',
        'license_plate' => 'ABC123',
    ]);

    Sanctum::actingAs($passenger);

    $data = [
        'start_address' => '123 Main St',
        'end_address' => '456 Elm St',
        'preference' => 'silent ride',
        'driver_id' => $driver->id,
    ];

    $response = $this->postJson('/api/v1/trips', $data);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'id',
            'start_address',
            'end_address',
            'status',
            'created_at',
            'updated_at',
        ])
        ->assertJsonFragment(['start_address' => '123 Main St']);

    $this->assertDatabaseHas('trips', [
        'passenger_id' => $passenger->id,
        'driver_id' => $driver->id,
        'start_address' => '123 Main St',
    ]);
});

it('fails to store trip with invalid data', function (): void {
    $passenger = User::create([
        'name' => 'Jane Smith',
        'phone' => '+123456783', 'email' => 'jane.smith@example.com',
        'password' => bcrypt('password123'),
        'role' => 'passenger',
    ]);

    Sanctum::actingAs($passenger);

    $data = [
        'start_address' => '',
        'end_address' => '',
        'preference' => '',
        'driver_id' => 999, // Invalid driver
    ];

    $response = $this->postJson('/api/v1/trips', $data);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['start_address', 'end_address', 'driver_id'],
        ]);
});

it('fails to store trip for unauthenticated user', function (): void {
    $data = [
        'start_address' => '123 Main St',
        'end_address' => '456 Elm St',
        'preference' => 'silent ride',
        'driver_id' => 1,
        'car_id' => 1,
    ];

    $response = $this->postJson('/api/v1/trips', $data);

    $response->assertStatus(401)
        ->assertJson(['message' => 'Unauthenticated.']);
});

it('shows a specific trip for authorized user', function (): void {
    $passenger = User::create([
        'name' => 'Jane Smith',
        'phone' => '+123456783', 'email' => 'jane.smith@example.com',
        'password' => bcrypt('password123'),
        'role' => 'passenger',
    ]);

    $driver = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789', 'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    $car = Car::create([
        'user_id' => $driver->id,
        'model' => 'Toyota Prius',
        'brand' => 'Toyota',
        'license_plate' => 'ABC123',
    ]);

    $trip = Trip::create([
        'passenger_id' => $passenger->id,
        'driver_id' => $driver->id,
        'start_address' => '123 Main St',
        'end_address' => '456 Elm St',
        'status' => TripStatusEnum::PENDING->value,
    ]);

    Sanctum::actingAs($passenger);

    $response = $this->getJson("/api/v1/trips/{$trip->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'id',
            'start_address',
            'end_address',
            'status',
            'created_at',
            'updated_at',
        ])
        ->assertJsonFragment(['start_address' => '123 Main St']);
});

it('fails to show trip for unauthorized user', function (): void {
    $passenger1 = User::create([
        'name' => 'Jane Smith',
        'phone' => '+123456783', 'email' => 'jane.smith@example.com',
        'password' => bcrypt('password123'),
        'role' => 'passenger',
    ]);

    $passenger2 = User::create([
        'name' => 'Bob Johnson',
        'phone' => '+123456784',
        'email' => 'bob.johnson@example.com',
        'password' => bcrypt('password123'),
        'role' => 'passenger',
    ]);

    $driver = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789', 'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    $car = Car::create([
        'user_id' => $driver->id,
        'model' => 'Toyota Prius',
        'brand' => 'Toyota',
        'license_plate' => 'ABC123',
    ]);

    $trip = Trip::create([
        'passenger_id' => $passenger1->id,
        'driver_id' => $driver->id,
        'start_address' => '123 Main St',
        'end_address' => '456 Elm St',
        'status' => TripStatusEnum::PENDING->value,
    ]);

    Sanctum::actingAs($passenger2);

    $response = $this->getJson("/api/v1/trips/{$trip->id}");

    $response->assertStatus(403)
        ->assertJson(['message' => 'This action is unauthorized.']);
});

it('fails to show non-existent trip', function (): void {
    $passenger = User::create([
        'name' => 'Jane Smith',
        'phone' => '+123456783', 'email' => 'jane.smith@example.com',
        'password' => bcrypt('password123'),
        'role' => 'passenger',
    ]);

    Sanctum::actingAs($passenger);

    $response = $this->getJson('/api/v1/trips/999');

    $response->assertStatus(404);
});

it('updates a trip for authorized user', function (): void {
    $passenger = User::create([
        'name' => 'Jane Smith',
        'phone' => '+123456783', 'email' => 'jane.smith@example.com',
        'password' => bcrypt('password123'),
        'role' => 'passenger',
    ]);

    $driver = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789', 'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    $car = Car::create([
        'user_id' => $driver->id,
        'model' => 'Toyota Prius',
        'brand' => 'Toyota',
        'license_plate' => 'ABC123',
    ]);

    $trip = Trip::create([
        'passenger_id' => $passenger->id,
        'driver_id' => $driver->id,
        'start_address' => '123 Main St',
        'end_address' => '456 Elm St',
        'status' => TripStatusEnum::PENDING->value,
    ]);

    Sanctum::actingAs($passenger);

    $data = [
        'start_address' => '789 Oak St',
        'end_address' => '101 Pine St',
        'preference' => 'music',
    ];

    $response = $this->patchJson("/api/v1/trips/{$trip->id}", $data);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'id',
            'start_address',
            'end_address',
            'status',
            'created_at',
            'updated_at',
        ])
        ->assertJsonFragment(['start_address' => '789 Oak St']);

    $this->assertDatabaseHas('trips', [
        'id' => $trip->id,
        'start_address' => '789 Oak St',
        'end_address' => '101 Pine St',
    ]);
});

it('fails to update trip with invalid data', function (): void {
    $passenger = User::create([
        'name' => 'Jane Smith',
        'phone' => '+123456783', 'email' => 'jane.smith@example.com',
        'password' => bcrypt('password123'),
        'role' => 'passenger',
    ]);

    $driver = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789', 'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    $car = Car::create([
        'user_id' => $driver->id,
        'model' => 'Toyota Prius',
        'brand' => 'Toyota',
        'license_plate' => 'ABC123',
    ]);

    $trip = Trip::create([
        'passenger_id' => $passenger->id,
        'driver_id' => $driver->id,
        'start_address' => '123 Main St',
        'end_address' => '456 Elm St',
        'status' => TripStatusEnum::PENDING->value,
    ]);

    Sanctum::actingAs($passenger);

    $data = [
        'start_address' => '',
        'end_address' => '',
        'preference' => '',
    ];

    $response = $this->patchJson("/api/v1/trips/{$trip->id}", $data);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['start_address', 'end_address'],
        ]);
});

it('fails to update trip for unauthorized user', function (): void {
    $passenger1 = User::create([
        'name' => 'Jane Smith',
        'phone' => '+123456783', 'email' => 'jane.smith@example.com',
        'password' => bcrypt('password123'),
        'role' => 'passenger',
    ]);

    $passenger2 = User::create([
        'name' => 'Bob Johnson',
        'phone' => '+123456784',
        'email' => 'bob.johnson@example.com',
        'password' => bcrypt('password123'),
        'role' => 'passenger',
    ]);

    $driver = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789', 'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    $car = Car::create([
        'user_id' => $driver->id,
        'model' => 'Toyota Prius',
        'brand' => 'Toyota',
        'license_plate' => 'ABC123',
    ]);

    $trip = Trip::create([
        'passenger_id' => $passenger1->id,
        'driver_id' => $driver->id,
        'start_address' => '123 Main St',
        'end_address' => '456 Elm St',
        'status' => TripStatusEnum::PENDING->value,
    ]);

    Sanctum::actingAs($passenger2);

    $data = [
        'start_address' => '789 Oak St',
        'end_address' => '101 Pine St',
        'preference' => 'music',
    ];

    $response = $this->patchJson("/api/v1/trips/{$trip->id}", $data);

    $response->assertStatus(403)
        ->assertJson(['message' => 'This action is unauthorized.']);
});

it('cancels a trip for authorized user', function (): void {
    $passenger = User::create([
        'name' => 'Jane Smith',
        'phone' => '+123456783', 'email' => 'jane.smith@example.com',
        'password' => bcrypt('password123'),
        'role' => 'passenger',
    ]);

    $driver = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789', 'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    $car = Car::create([
        'user_id' => $driver->id,
        'model' => 'Toyota Prius',
        'brand' => 'Toyota',
        'license_plate' => 'ABC123',
    ]);

    $trip = Trip::create([
        'passenger_id' => $passenger->id,
        'driver_id' => $driver->id,
        'car_id' => $car->id,
        'start_address' => '123 Main St',
        'end_address' => '456 Elm St',
        'status' => TripStatusEnum::PENDING->value,
    ]);

    Sanctum::actingAs($passenger);

    $response = $this->deleteJson("/api/v1/trips/{$trip->id}");

    $response->assertStatus(200)
        ->assertJson(['message' => 'Trip canceled']);

    $this->assertDatabaseHas('trips', [
        'id' => $trip->id,
        'status' => TripStatusEnum::CANCELED->value,
    ]);
});

it('fails to cancel trip for Unauthenticated. user', function (): void {
    $passenger1 = User::create([
        'name' => 'Jane Smith',
        'phone' => '+123456783', 'email' => 'jane.smith@example.com',
        'password' => bcrypt('password123'),
        'role' => 'passenger',
    ]);

    $passenger2 = User::create([
        'name' => 'Bob Johnson',
        'phone' => '+123456784',
        'email' => 'bob.johnson@example.com',
        'password' => bcrypt('password123'),
        'role' => 'passenger',
    ]);

    $driver = User::create([
        'name' => 'John Doe',
        'phone' => '+123456789', 'email' => 'john.doe@example.com',
        'password' => bcrypt('password123'),
        'role' => 'driver',
    ]);

    $car = Car::create([
        'user_id' => $driver->id,
        'model' => 'Toyota Prius',
        'brand' => 'Toyota',
        'license_plate' => 'ABC123',
    ]);

    $trip = Trip::create([
        'passenger_id' => $passenger1->id,
        'driver_id' => $driver->id,
        'car_id' => $car->id,
        'start_address' => '123 Main St',
        'end_address' => '456 Elm St',
        'status' => TripStatusEnum::PENDING->value,
    ]);

    Sanctum::actingAs($passenger2);

    $response = $this->deleteJson("/api/v1/trips/{$trip->id}");

    $response->assertStatus(403)
        ->assertJson(['message' => 'This action is unauthorized.']);
});

it('fails to cancel non-existent trip', function (): void {
    $passenger = User::create([
        'name' => 'Jane Smith',
        'phone' => '+123456783', 'email' => 'jane.smith@example.com',
        'password' => bcrypt('password123'),
        'role' => 'passenger',
    ]);

    Sanctum::actingAs($passenger);

    $response = $this->deleteJson('/api/v1/trips/999');

    $response->assertStatus(404);
});
