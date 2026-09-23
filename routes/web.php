<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\Directories;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDetailsController;
use App\Http\Controllers\EmployeeStatusController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
    Route::get('search', SearchController::class)->name('search');
    Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::get('departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');
});

Route::middleware(['auth', 'can:manage-employees'])->prefix('employees/{employee}')->name('employees.')->group(function () {
    // One card of the profile at a time, edited from its own dialog.
    Route::put('personal', [EmployeeDetailsController::class, 'personal'])->name('personal');
    Route::put('passport', [EmployeeDetailsController::class, 'passport'])->name('passport');
    Route::put('contacts', [EmployeeDetailsController::class, 'contacts'])->name('contacts');
    Route::put('languages', [EmployeeDetailsController::class, 'languages'])->name('languages');
    Route::put('family', [EmployeeDetailsController::class, 'family'])->name('family');
    Route::post('transfer', [EmployeeStatusController::class, 'transfer'])->name('transfer');
    Route::post('fire', [EmployeeStatusController::class, 'fire'])->name('fire');
    Route::post('restore', [EmployeeStatusController::class, 'restore'])->name('restore');
    Route::delete('/', [EmployeeStatusController::class, 'destroy'])->name('destroy');
});

// Directories: roles ("Позиция"), positions ("Должность"), departments, languages and equipment.
Route::middleware(['auth', 'can:manage-directories'])->prefix('directories')->name('directories.')->group(function () {
    Route::redirect('/', '/directories/roles');

    Route::resource('roles', Directories\RoleController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('positions', Directories\PositionController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('departments', Directories\DepartmentController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('languages', Directories\LanguageController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('equipment', Directories\EquipmentTypeController::class)->only(['index', 'store', 'update', 'destroy']);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
