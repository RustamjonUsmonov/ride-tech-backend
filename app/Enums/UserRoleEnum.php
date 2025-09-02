<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRoleEnum: string
{
    case PASSENGER = 'passenger';
    case DRIVER = 'driver';
}
