<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCarRequest;
use App\Http\Resources\CarResource;
use App\Http\Services\CarService;
use App\Models\Car;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class CarController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/cars",
     *     summary="Get a list of cars for the authenticated user",
     *     tags={"Cars"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of cars retrieved successfully",
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
     *                     @OA\Property(property="model", type="string", example="Model S"),
     *                     @OA\Property(property="brand", type="string", example="Tesla"),
     *                     @OA\Property(property="license_plate", type="string", example="ABC123"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-02T12:00:00Z")
     *                 )
     *             ),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
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
    public function index(): AnonymousResourceCollection
    {
        $user = auth('sanctum')->user();
        Gate::authorize('index', Car::class, $user);

        return Cache::remember("user_{$user->id}_cars", 60, function () use ($user) {
            $cars = $user->cars()->paginate(10);

            return CarResource::collection($cars);
        });
    }

    /**
     * @OA\Post(
     *     path="/api/v1/cars",
     *     summary="Store a new car for the authenticated user",
     *     tags={"Cars"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="model", type="string", example="Model S"),
     *             @OA\Property(property="brand", type="string", example="Tesla"),
     *             @OA\Property(property="license_plate", type="string", example="ABC123")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Car created successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="model", type="string", example="Model S"),
     *             @OA\Property(property="brand", type="string", example="Tesla"),
     *             @OA\Property(property="license_plate", type="string", example="ABC123"),
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
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="model", type="array",
     *
     *                     @OA\Items(type="string", example="The model field is required.")
     *                 ),
     *
     *                 @OA\Property(property="brand", type="array",
     *
     *                     @OA\Items(type="string", example="The brand field is required.")
     *                 ),
     *
     *                 @OA\Property(property="license_plate", type="array",
     *
     *                     @OA\Items(type="string", example="The license plate field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function store(StoreCarRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = auth('sanctum')->user();
        Gate::authorize('store', Car::class, $user);

        $car = app(CarService::class)->create($validated);
        Cache::forget("user_{$user->id}_cars");

        return response()->json(new CarResource($car), 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/cars/{car_id}",
     *     summary="Delete a car owned by the authenticated user",
     *     tags={"Cars"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="car_id",
     *         in="path",
     *         required=true,
     *         description="ID of the car to delete",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Car deleted successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Car deleted")
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
     *         response=403,
     *         description="Forbidden",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="error", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Car not found",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="error", type="string", example="No query results for model [App\\Models\\Car] 1")
     *         )
     *     )
     * )
     */
    public function destroy(Car $car): JsonResponse
    {
        Gate::authorize('delete', $car);
        Cache::forget("user_{$car->user_id}_cars");
        $car->delete();

        return response()->json(['message' => 'Car deleted']);
    }
}
