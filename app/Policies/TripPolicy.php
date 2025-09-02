<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\TripStatusEnum;
use App\Models\Trip;
use App\Models\User;

class TripPolicy
{
    public function view(User $user, Trip $trip): bool
    {
        return $user->id === $trip->passenger_id || $user->id === $trip->driver_id;
    }

    public function update(User $user, Trip $trip): bool
    {
        return $user->id === $trip->passenger_id && $trip->status === TripStatusEnum::PENDING->value;
    }

    public function delete(User $user, Trip $trip): bool
    {
        return $user->id === $trip->passenger_id && $trip->status === TripStatusEnum::PENDING->value;
    }
}
