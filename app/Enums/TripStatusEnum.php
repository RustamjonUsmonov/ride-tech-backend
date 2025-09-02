<?php

declare(strict_types=1);

namespace App\Enums;

enum TripStatusEnum: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case COMPLETED = 'completed';
    case CANCELED = 'canceled';
}
