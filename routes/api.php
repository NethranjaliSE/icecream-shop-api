<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ItemController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Every route here is prefixed with /api and always answers in JSON.
| Protected routes use the Passport "api" guard (Authorization: Bearer ...).
|
*/

Route::get('/', fn () => ['name' => config('app.name'), 'status' => 'ok']);

Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login');

    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    });
});

Route::middleware('auth:api')->group(function () {
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');

    // Role checks live in each controller's middleware() via ItemPolicy / CustomerPolicy.
    Route::apiResource('items', ItemController::class);
    Route::apiResource('customers', CustomerController::class);
});
