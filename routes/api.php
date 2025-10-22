<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them
| will be assigned to the "api" middleware group.
|
*/

// Public routes (no authentication required)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])
        ->name('api.auth.register');
    
    Route::post('/login', [AuthController::class, 'login'])
        ->name('api.auth.login');
});

// Protected routes (authentication required via Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('api.auth.logout');
        
        Route::get('/me', [AuthController::class, 'me'])
            ->name('api.auth.me');
    });
    
    // Orders routes will be added here
    // Route::resource('orders', OrderController::class);
    // Route::get('clients/{id}/orders', [ClientController::class, 'orders']);
});

