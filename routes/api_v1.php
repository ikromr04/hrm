<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\{
    AuthController,
    DepartmentController,
    EquipmentController,
    ExperienceController,
    LanguageController,
    PositionController,
    RoleController,
    UserController,
    ProfileController,
};

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::get('/me', 'me')->name('me');
        Route::delete('/logout', 'logout')->name('logout');
        Route::delete('/tokens', 'logoutAll')->name('logoutAll');
    });

    Route::apiResource('users', UserController::class);

    Route::apiResource('profiles', ProfileController::class);

    Route::apiResource('roles', RoleController::class);

    Route::apiResource('positions', PositionController::class);

    Route::apiResource('departments', DepartmentController::class);

    Route::apiResource('languages', LanguageController::class);

    Route::apiResource('equipments', EquipmentController::class);

    Route::apiResource('experiences', ExperienceController::class);
});
