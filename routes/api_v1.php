<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\EquipmentController;
use App\Http\Controllers\Api\V1\LanguageController;
use App\Http\Controllers\Api\V1\PositionController;
use App\Http\Controllers\Api\V1\RoleController;
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

    Route::apiResource('users', UserController::class);

    Route::apiResource('user-details', UserDetailController::class);

    Route::apiResource('roles', RoleController::class);

    Route::apiResource('positions', PositionController::class);

    Route::apiResource('departments', DepartmentController::class);

    Route::apiResource('languages', LanguageController::class);

    Route::apiResource('equipments', EquipmentController::class);
});
