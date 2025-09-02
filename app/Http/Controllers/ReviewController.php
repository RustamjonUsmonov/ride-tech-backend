<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Http\Services\ReviewService;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/reviews/{driver_id}",
     *     summary="Get a paginated list of reviews for a specific driver",
     *     tags={"Reviews"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="driver_id",
     *         in="path",
     *         required=true,
     *         description="ID of the driver whose reviews are being fetched",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of reviews retrieved successfully",
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
     *                         property="driver",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="John Doe")
     *                     ),
     *                     @OA\Property(
     *                         property="passenger",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="name", type="string", example="Jane Smith")
     *                     ),
     *                     @OA\Property(property="rating", type="integer", example=5),
     *                     @OA\Property(property="comment", type="string", example="Great ride!"),
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
    public function index(int $driver_id): AnonymousResourceCollection
    {
        return Cache::remember("driver_{$driver_id}_reviews", 60, function () use ($driver_id) {
            $reviews = Review::where('driver_id', $driver_id)
                ->with(['driver:id,name', 'passenger:id,name'])
                ->paginate(10);

            return ReviewResource::collection($reviews);
        });
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reviews/{driver_id}",
     *     summary="Submit a review for a specific driver",
     *     tags={"Reviews"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="driver_id",
     *         in="path",
     *         required=true,
     *         description="ID of the driver being reviewed",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             type="object",
     *             required={"rating"},
     *
     *             @OA\Property(property="rating", type="integer", format="int32", minimum=1, maximum=5, example=5, description="Rating given to the driver"),
     *             @OA\Property(property="comment", type="string", example="Great ride!", description="Optional comment about the ride")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Review created successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(
     *                     property="driver",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe")
     *                 ),
     *                 @OA\Property(
     *                     property="passenger",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Jane Smith")
     *                 ),
     *                 @OA\Property(property="rating", type="integer", example=5),
     *                 @OA\Property(property="comment", type="string", example="Great ride!"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z"),
     *            @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-02T12:00:00Z")
     *            )
     *        )
     *    ),
     *
     *     @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *
     *          @OA\JsonContent(
     *              type="object",
     *
     *              @OA\Property(property="error", type="string", example="Unauthorized")
     *          )
     *      )
     *  )
     */
    public function store(StoreReviewRequest $request, int $driver_id): JsonResponse
    {
        Gate::authorize('store', [Review::class, $driver_id]);
        $review = app(ReviewService::class)->create($request->validated(), $driver_id, auth('sanctum')->id());
        Cache::forget("driver_{$driver_id}_reviews");

        return response()->json(new ReviewResource($review), 201);
    }
}
