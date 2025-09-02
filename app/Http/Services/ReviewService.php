<?php

declare(strict_types=1);

namespace App\Http\Services;

use App\Models\Review;

class ReviewService
{
    public function create(array $data, int $driverId, int $passengerId): Review
    {
        return Review::create([
            'driver_id' => $driverId,
            'passenger_id' => $passengerId,
            ...$data,
        ]);
    }
}
