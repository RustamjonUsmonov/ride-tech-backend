<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\TripController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('trips', TripController::class);
    Route::apiResource('cars', CarController::class)->only(['index', 'store', 'destroy']);
    Route::post('/reviews/{driver_id}', [ReviewController::class, 'store'])->name('reviews.store');
    Route::get('/reviews/{driver_id}', [ReviewController::class, 'index'])->name('reviews.index');
});
