<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Car extends Model
{
    protected $fillable = ['user_id', 'model', 'brand', 'license_plate'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
