<?php

declare(strict_types=1);

namespace App\Http\Services;

use Illuminate\Database\Eloquent\Model;

class CarService
{
    public function create(array $data): Model
    {
        return auth('sanctum')->user()->cars()->create($data);
    }
}
