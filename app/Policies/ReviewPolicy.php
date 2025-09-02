<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\TripStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\User;

class ReviewPolicy
{
    public function store(User $user, int $driverId): bool
    {
        return $user->role->value === UserRoleEnum::PASSENGER->value
            && $user->trips()->where('status', TripStatusEnum::COMPLETED->value)->exists();
    }
}
