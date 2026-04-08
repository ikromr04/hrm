<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\UserDepartmentRelationshipController;
use App\Http\Controllers\Api\V1\UserDetailController;
use App\Http\Controllers\Api\V1\UserUserDetailRelationshipController;
use App\Http\Controllers\Api\V1\UserEducationRelationshipController;
use App\Http\Controllers\Api\V1\UserEquipmentRelationshipController;
use App\Http\Controllers\Api\V1\UserExperienceRelationshipController;
use App\Http\Controllers\Api\V1\UserLanguageRelationshipController;
use App\Http\Controllers\Api\V1\UserPositionRelationshipController;
use App\Http\Controllers\Api\V1\UserRoleRelationshipController;
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

        Route::get('users/{user}/user-details', 'userDetails')->name('users.user-details');
        Route::get('users/{user}/roles', 'roles')->name('users.roles');
        Route::get('users/{user}/positions', 'positions')->name('users.positions');
        Route::get('users/{user}/departments', 'departments')->name('users.departments');
        Route::get('users/{user}/experiences', 'experiences')->name('users.experiences');
        Route::get('users/{user}/educations', 'educations')->name('users.educations');
        Route::get('users/{user}/equipments', 'equipments')->name('users.equipments');
        Route::get('users/{user}/languages', 'languages')->name('users.languages');
    });

    Route::controller(UserUserDetailRelationshipController::class)->group(function () {
        Route::get('users/{user}/relationships/user-details', 'show')->name('users.relationships.user-details');
        Route::post('users/{user}/relationships/user-details', 'store')->name('users.relationships.user-details.store');
        Route::patch('users/{user}/relationships/user-details', 'update')->name('users.relationships.user-details.update');
        Route::delete('users/{user}/relationships/user-details', 'destroy')->name('users.relationships.user-details.destroy');
    });

    Route::controller(UserRoleRelationshipController::class)->group(function () {
        Route::get('users/{user}/relationships/roles', 'index')->name('users.relationships.roles');
        Route::post('users/{user}/relationships/roles', 'store')->name('users.relationships.roles.store');
        Route::patch('users/{user}/relationships/roles', 'update')->name('users.relationships.roles.update');
        Route::delete('users/{user}/relationships/roles', 'destroy')->name('users.relationships.roles.destroy');
    });

    Route::controller(UserPositionRelationshipController::class)->group(function () {
        Route::get('users/{user}/relationships/positions', 'index')->name('users.relationships.positions');
        Route::post('users/{user}/relationships/positions', 'store')->name('users.relationships.positions.store');
        Route::patch('users/{user}/relationships/positions', 'update')->name('users.relationships.positions.update');
        Route::delete('users/{user}/relationships/positions', 'destroy')->name('users.relationships.positions.destroy');
    });

    Route::controller(UserDepartmentRelationshipController::class)->group(function () {
        Route::get('users/{user}/relationships/departments', 'index')->name('users.relationships.departments');
        Route::post('users/{user}/relationships/departments', 'store')->name('users.relationships.departments.store');
        Route::patch('users/{user}/relationships/departments', 'update')->name('users.relationships.departments.update');
        Route::delete('users/{user}/relationships/departments', 'destroy')->name('users.relationships.departments.destroy');
    });

    Route::controller(UserExperienceRelationshipController::class)->group(function () {
        Route::get('users/{user}/relationships/experiences', 'index')->name('users.relationships.experiences');
        Route::post('users/{user}/relationships/experiences', 'store')->name('users.relationships.experiences.store');
        Route::patch('users/{user}/relationships/experiences', 'update')->name('users.relationships.experiences.update');
        Route::delete('users/{user}/relationships/experiences', 'destroy')->name('users.relationships.experiences.destroy');
    });

    Route::controller(UserEducationRelationshipController::class)->group(function () {
        Route::get('users/{user}/relationships/educations', 'index')->name('users.relationships.educations');
        Route::post('users/{user}/relationships/educations', 'store')->name('users.relationships.educations.store');
        Route::patch('users/{user}/relationships/educations', 'update')->name('users.relationships.educations.update');
        Route::delete('users/{user}/relationships/educations', 'destroy')->name('users.relationships.educations.destroy');
    });

    Route::controller(UserEquipmentRelationshipController::class)->group(function () {
        Route::get('users/{user}/relationships/equipments', 'index')->name('users.relationships.equipments');
        Route::post('users/{user}/relationships/equipments', 'store')->name('users.relationships.equipments.store');
        Route::patch('users/{user}/relationships/equipments', 'update')->name('users.relationships.equipments.update');
        Route::delete('users/{user}/relationships/equipments', 'destroy')->name('users.relationships.equipments.destroy');
    });

    Route::controller(UserLanguageRelationshipController::class)->group(function () {
        Route::get('users/{user}/relationships/languages', 'index')->name('users.relationships.languages');
        Route::post('users/{user}/relationships/languages', 'store')->name('users.relationships.languages.store');
        Route::patch('users/{user}/relationships/languages', 'update')->name('users.relationships.languages.update');
        Route::delete('users/{user}/relationships/languages', 'destroy')->name('users.relationships.languages.destroy');
    });

    Route::controller(UserDetailController::class)->group(function () {
        Route::get('user-details', 'index')->name('user-details.index');
        Route::post('user-details', 'store')->name('user-details.store');
        Route::get('user-details/{user-detail}', 'show')->name('user-details.show');
        Route::patch('user-details/{user-detail}', 'update')->name('user-details.update');
        Route::delete('user-details/{user-detail}', 'destroy')->name('user-details.destroy');
    });
});
