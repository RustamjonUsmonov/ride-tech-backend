<?php

declare(strict_types=1);

namespace App\Http\Services;

use App\Enums\TripStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class TripService
{
    public function create(array $data): Model
    {
        $driver = User::where('id', $data['driver_id'])->first();
        if (!$driver || $driver->role->value !== UserRoleEnum::DRIVER->value || $driver->id === auth('sanctum')->id()) {
            throw new InvalidArgumentException('Invalid driver ID');
        }

        return auth('sanctum')->user()->trips()->create($data + ['status' => TripStatusEnum::PENDING->value]);
    }

    public function update(array $data, Trip $trip): bool
    {
        return $trip->update($data);
    }

    public function updateStatus(Trip $trip, TripStatusEnum $status): bool
    {
        return $trip->update(['status' => $status->value]);
    }
}
