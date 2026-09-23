<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\Directories;
use App\Http\Controllers\EmployeeAvatarController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDetailsController;
use App\Http\Controllers\EmployeeEducationController;
use App\Http\Controllers\EmployeeStatusController;
use App\Http\Controllers\EmployeeWorkExperienceController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EquipmentStatusController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
    Route::get('search', SearchController::class)->name('search');
    Route::get('equipment', [EquipmentController::class, 'index'])->name('equipment.index');
    Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::get('departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');
});

Route::middleware(['auth', 'can:manage-employees'])->prefix('employees/{employee}')->name('employees.')->group(function () {
    // One card of the profile at a time, edited from its own dialog.
    // Multipart, so the upload is a POST rather than a PUT.
    Route::post('avatar', [EmployeeAvatarController::class, 'update'])->name('avatar.update');
    Route::delete('avatar', [EmployeeAvatarController::class, 'destroy'])->name('avatar.destroy');

    Route::put('personal', [EmployeeDetailsController::class, 'personal'])->name('personal');
    Route::put('passport', [EmployeeDetailsController::class, 'passport'])->name('passport');
    Route::put('contacts', [EmployeeDetailsController::class, 'contacts'])->name('contacts');
    Route::put('languages', [EmployeeDetailsController::class, 'languages'])->name('languages');
    Route::put('employment', [EmployeeDetailsController::class, 'employment'])->name('employment');

    // Education is kept record by record. The controller checks that the record
    // belongs to the employee in the URL, so one person's id cannot reach
    // another's; scoped bindings would not, as "education" has no plural form
    // for Laravel to find the relation by.
    Route::post('educations', [EmployeeEducationController::class, 'store'])->name('educations.store');
    Route::put('educations/{education}', [EmployeeEducationController::class, 'update'])->name('educations.update');
    Route::delete('educations/{education}', [EmployeeEducationController::class, 'destroy'])->name('educations.destroy');

    Route::post('experiences', [EmployeeWorkExperienceController::class, 'store'])->name('experiences.store');
    Route::put('experiences/{experience}', [EmployeeWorkExperienceController::class, 'update'])->name('experiences.update');
    Route::delete('experiences/{experience}', [EmployeeWorkExperienceController::class, 'destroy'])->name('experiences.destroy');

    Route::put('family', [EmployeeDetailsController::class, 'family'])->name('family');
    Route::post('transfer', [EmployeeStatusController::class, 'transfer'])->name('transfer');
    Route::post('fire', [EmployeeStatusController::class, 'fire'])->name('fire');
    Route::post('restore', [EmployeeStatusController::class, 'restore'])->name('restore');
    Route::delete('/', [EmployeeStatusController::class, 'destroy'])->name('destroy');
});

// Putting a new unit on the books.
Route::post('equipment', [EquipmentController::class, 'store'])
    ->middleware(['auth', 'can:manage-employees'])
    ->name('equipment.store');

// A unit's life: handed out, taken back, repaired, written off.
Route::middleware(['auth', 'can:manage-employees'])->prefix('equipment/{equipment}')->name('equipment.')->group(function () {
    Route::post('issue', [EquipmentStatusController::class, 'issue'])->name('issue');
    Route::post('take', [EquipmentStatusController::class, 'take'])->name('take');
    Route::post('repair', [EquipmentStatusController::class, 'repair'])->name('repair');
    Route::post('write-off', [EquipmentStatusController::class, 'writeOff'])->name('write-off');
});

// Directories: roles ("Позиция"), positions ("Должность"), departments, languages and equipment categories.
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
