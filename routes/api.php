<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Every route here is prefixed with /api and always answers in JSON.
|
*/

Route::get('/', fn () => ['name' => config('app.name'), 'status' => 'ok']);
