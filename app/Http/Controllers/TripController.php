<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TripStatusEnum;
use App\Http\Requests\IndexTripRequest;
use App\Http\Requests\StoreTripRequest;
use App\Http\Requests\UpdateTripRequest;
use App\Http\Resources\TripResource;
use App\Http\Services\TripService;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TripController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/trips",
     *     summary="Get a paginated list of trips for the authenticated passenger",
     *     tags={"Trips"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter trips by status",
     *
     *         @OA\Schema(type="string", example="completed")
     *     ),
     *
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         required=false,
     *         description="Filter trips by creation date",
     *
     *         @OA\Schema(type="string", format="date", example="2023-01-01")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of trips retrieved successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(
     *                     type="object",
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(
     *                         property="passenger",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="name", type="string", example="Jane Smith")
     *                     ),
     *                     @OA\Property(
     *                         property="driver",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="id", type="integer", example=3),
     *                         @OA\Property(property="name", type="string", example="John Doe")
     *                     ),
     *                     @OA\Property(
     *                         property="car",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="model", type="string", example="Toyota Prius")
     *                     ),
     *                     @OA\Property(property="start_address", type="string", example="123 Main St"),
     *                     @OA\Property(property="end_address", type="string", example="456 Elm St"),
     *                     @OA\Property(property="status", type="string", example="completed"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-02T12:00:00Z")
     *                 )
     *             ),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function index(IndexTripRequest $request): AnonymousResourceCollection
    {
        $trips = Trip::query()->where('passenger_id', auth('sanctum')->id())
            ->when($request->status, function (Builder $query, string $status): void {
                $query->where('status', $status);
            })
            ->when($request->date, function (Builder $query, string $date): void {
                $query->whereDate('created_at', $date);
            })
            ->with(['driver', 'passenger'])
            ->paginate(10);

        return TripResource::collection($trips);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/trips",
     *     summary="Create a new trip",
     *     tags={"Trips"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             type="object",
     *             required={"start_address", "end_address", "driver_id", "car_id"},
     *
     *             @OA\Property(property="start_address", type="string", example="123 Main St", description="Starting location of the trip"),
     *             @OA\Property(property="end_address", type="string", example="456 Elm St", description="Ending location of the trip"),
     *             @OA\Property(property="preference", type="string", example="silent ride", description="Passenger's ride preference"),
     *             @OA\Property(property="driver_id", type="integer")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Trip created successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(
     *                     property="passenger",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Jane Smith")
     *                 ),
     *                 @OA\Property(
     *                     property="driver",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(property="id", type="integer", example=3),
     *                     @OA\Property(property="name", type="string", example="John Doe")
     *                 ),
     *                 @OA\Property(
     *                     property="car",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="model", type="string", example="Toyota Prius")
     *                 ),
     *                 @OA\Property(property="start_address", type="string", example="123 Main St"),
     *                 @OA\Property(property="end_address", type="string", example="456 Elm St"),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-02T12:00:00Z")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function store(StoreTripRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $trip = app(TripService::class)->create($validated);

        return response()->json(new TripResource($trip), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/trips/{id}",
     *     summary="Get details of a specific trip",
     *     tags={"Trips"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the trip to retrieve",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Trip details retrieved successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(
     *                 property="passenger",
     *                 type="object",
     *                 nullable=true,
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="name", type="string", example="Jane Smith")
     *             ),
     *             @OA\Property(
     *                 property="driver",
     *                 type="object",
     *                 nullable=true,
     *                 @OA\Property(property="id", type="integer", example=3),
     *                 @OA\Property(property="name", type="string", example="John Doe")
     *             ),
     *             @OA\Property(
     *                 property="car",
     *                 type="object",
     *                 nullable=true,
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="model", type="string", example="Toyota Prius")
     *             ),
     *             @OA\Property(property="start_address", type="string", example="123 Main St"),
     *             @OA\Property(property="end_address", type="string", example="456 Elm St"),
     *             @OA\Property(property="status", type="string", example="completed"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-02T12:00:00Z")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Not Found",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="error", type="string", example="Trip not found")
     *         )
     *     )
     * )
     */
    public function show(Trip $trip): JsonResponse
    {
        Gate::authorize('view', $trip);

        return response()->json(new TripResource($trip));
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/trips/{id}",
     *     summary="Update a trip",
     *     tags={"Trips"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the trip to update",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="start_address",
     *                 type="string",
     *                 nullable=true,
     *                 description="Starting address of the trip. Required if 'end_address' and 'preferences' are not provided.",
     *                 example="123 Main St"
     *             ),
     *             @OA\Property(
     *                 property="end_address",
     *                 type="string",
     *                 nullable=true,
     *                 description="Ending address of the trip. Required if 'start_address' and 'preferences' are not provided.",
     *                 example="456 Elm St"
     *             ),
     *             @OA\Property(
     *                 property="preferences",
     *                 type="string",
     *                 nullable=true,
     *                 description="Passenger's ride preferences. Required if 'start_address' and 'end_address' are not provided.",
     *                 example="silent ride"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Trip updated successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Trip updated successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Not Found",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="error", type="string", example="Trip not found")
     *         )
     *     )
     * )
     */
    public function update(UpdateTripRequest $request, Trip $trip): JsonResponse
    {
        Gate::authorize('update', $trip);
        app(TripService::class)->update($request->validated(), $trip);

        return response()->json(new TripResource($trip));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/trips/{id}",
     *     summary="Cancel a trip",
     *     tags={"Trips"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the trip to cancel",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Trip canceled successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Trip canceled")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Not Found",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="error", type="string", example="Trip not found")
     *         )
     *     )
     * )
     */
    public function destroy(Trip $trip): JsonResponse
    {
        Gate::authorize('delete', $trip);
        app(TripService::class)->updateStatus($trip, TripStatusEnum::CANCELED);

        return response()->json(['message' => 'Trip canceled']);
    }
}
