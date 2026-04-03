<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::get('/me', 'me')->name('me');
        Route::delete('/logout', 'logout')->name('logout');
        Route::delete('/tokens', 'logoutAll')->name('logoutAll');
    });
});
