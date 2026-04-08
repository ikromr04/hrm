<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\UserDetailController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::get('/me', 'me')->name('me');
        Route::delete('/logout', 'logout')->name('logout');
        Route::delete('/tokens', 'logoutAll')->name('logoutAll');
    });

    Route::controller(UserController::class)->group(function () {
        Route::get('users', 'index')->name('users.index');
        Route::post('users', 'store')->name('users.store');
        Route::get('users/{user}', 'show')->name('users.show');
        Route::patch('users/{user}', 'update')->name('users.update');
        Route::delete('users/{user}', 'destroy')->name('users.destroy');
        Route::post('users/{user}/avatar', 'uploadAvatar')->name('users.avatar');
    });

    Route::controller(UserDetailController::class)->group(function () {
        Route::get('user-details', 'index')->name('user-details.index');
        Route::post('user-details', 'store')->name('user-details.store');
        Route::get('user-details/{user-detail}', 'show')->name('user-details.show');
        Route::patch('user-details/{user-detail}', 'update')->name('user-details.update');
        Route::delete('user-details/{user-detail}', 'destroy')->name('user-details.destroy');
    });
});
