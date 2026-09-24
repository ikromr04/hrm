<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\Directories;
use App\Http\Controllers\EmployeeAvatarController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDetailsController;
use App\Http\Controllers\EmployeeEducationController;
use App\Http\Controllers\EmployeeEquipmentController;
use App\Http\Controllers\EmployeeStatusController;
use App\Http\Controllers\EmployeeWorkExperienceController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EquipmentDetailsController;
use App\Http\Controllers\EquipmentDocumentController;
use App\Http\Controllers\EquipmentRepairController;
use App\Http\Controllers\EquipmentStatusController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
    // Before the profile, or "create" would be read as somebody's id.
    Route::get('employees/create', [EmployeeController::class, 'create'])
        ->middleware('can:manage-employees')
        ->name('employees.create');
    Route::get('employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
    Route::get('search', SearchController::class)->name('search');
    Route::get('equipment', [EquipmentController::class, 'index'])->name('equipment.index');
    Route::get('equipment/{equipment}', [EquipmentController::class, 'show'])->name('equipment.show');
    // Time off: everyone sees their own, heads and HR see everybody's.
    Route::get('leave', [LeaveController::class, 'index'])->name('leave.index');
    Route::post('leave', [LeaveRequestController::class, 'store'])->name('leave.store');
    Route::post('leave/{leave}/approve', [LeaveRequestController::class, 'approve'])->name('leave.approve');
    Route::post('leave/{leave}/reject', [LeaveRequestController::class, 'reject'])->name('leave.reject');
    Route::post('leave/{leave}/cancel', [LeaveRequestController::class, 'cancel'])->name('leave.cancel');

    Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::get('departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');
});

// Putting a new colleague on the books.
Route::post('employees', [EmployeeController::class, 'store'])
    ->middleware(['auth', 'can:manage-employees'])
    ->name('employees.store');

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
    // Several records in one request: the steps of the "new colleague" wizard.
    Route::post('educations/many', [EmployeeEducationController::class, 'storeMany'])->name('educations.many');
    Route::put('educations/{education}', [EmployeeEducationController::class, 'update'])->name('educations.update');
    Route::delete('educations/{education}', [EmployeeEducationController::class, 'destroy'])->name('educations.destroy');

    Route::post('experiences', [EmployeeWorkExperienceController::class, 'store'])->name('experiences.store');
    Route::post('experiences/many', [EmployeeWorkExperienceController::class, 'storeMany'])->name('experiences.many');
    Route::post('equipment', [EmployeeEquipmentController::class, 'store'])->name('equipment.store');
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

    // The card, edited one block at a time.
    Route::put('specs', [EquipmentDetailsController::class, 'specs'])->name('specs');
    Route::put('accessories', [EquipmentDetailsController::class, 'accessories'])->name('accessories');
    Route::put('state', [EquipmentDetailsController::class, 'state'])->name('state');
    Route::put('handover', [EquipmentDetailsController::class, 'handover'])->name('handover');

    // What has been done to it, and the papers that came with it.
    Route::post('repairs', [EquipmentRepairController::class, 'store'])->name('repairs.store');
    Route::delete('repairs/{repair}', [EquipmentRepairController::class, 'destroy'])->name('repairs.destroy');
    Route::post('documents', [EquipmentDocumentController::class, 'store'])->name('documents.store');
    Route::delete('documents/{document}', [EquipmentDocumentController::class, 'destroy'])->name('documents.destroy');
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
