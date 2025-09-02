<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRoleEnum;
use App\Models\Car;
use App\Models\User;

class CarPolicy
{
    public function index(User $user): bool
    {
        return $user->role->value === UserRoleEnum::DRIVER->value;
    }

    public function store(User $user): bool
    {
        return $user->role->value === UserRoleEnum::DRIVER->value;
    }

    public function delete(User $user, Car $car): bool
    {
        return ($user->role->value === UserRoleEnum::DRIVER->value) && ($user->id === $car->user_id);
    }
}
