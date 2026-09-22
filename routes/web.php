<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Directories;
use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
});

// Directories: roles ("Позиция"), positions ("Должность") and departments.
Route::middleware(['auth', 'can:manage-directories'])->prefix('directories')->name('directories.')->group(function () {
    Route::redirect('/', '/directories/roles');

    Route::resource('roles', Directories\RoleController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('positions', Directories\PositionController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('departments', Directories\DepartmentController::class)->only(['index', 'store', 'update', 'destroy']);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
