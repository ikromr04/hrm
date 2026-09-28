<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\Directories;
use App\Http\Controllers\EmployeeAccessController;
use App\Http\Controllers\EmployeeAvatarController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDetailsController;
use App\Http\Controllers\EmployeeEducationController;
use App\Http\Controllers\EmployeeEquipmentController;
use App\Http\Controllers\EmployeeStatusController;
use App\Http\Controllers\EmployeeWorkExperienceController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EquipmentDetailsController;
use App\Http\Controllers\EquipmentJournalController;
use App\Http\Controllers\EquipmentRepairController;
use App\Http\Controllers\EquipmentStatusController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('employees', [EmployeeController::class, 'index'])
        ->middleware('can:employees.view')
        ->name('employees.index');
    // Before the profile, or "create" would be read as somebody's id.
    Route::get('employees/create', [EmployeeController::class, 'create'])
        ->middleware('can:employees.manage')
        ->name('employees.create');
    // Everybody reaches their own card, whatever rights they hold.
    Route::get('employees/{employee}', [EmployeeController::class, 'show'])
        ->middleware('can:view,employee')
        ->name('employees.show');
    // Open to everybody: it only ever searches the sections the viewer may see.
    Route::get('search', SearchController::class)->name('search');
    Route::get('equipment', [EquipmentController::class, 'index'])
        ->middleware('can:equipment.view')
        ->name('equipment.index');
    // Before the card, or "journal" would be read as a unit's id. What went on
    // over a period is a right of its own.
    Route::get('equipment/journal', EquipmentJournalController::class)
        ->middleware('can:equipment.journal')
        ->name('equipment.journal');
    // Before the card too, for the same reason as the journal.
    Route::get('equipment/create', [EquipmentController::class, 'create'])
        ->middleware('can:equipment.manage')
        ->name('equipment.create');
    Route::get('equipment/{equipment}', [EquipmentController::class, 'show'])
        ->middleware('can:equipment.view')
        ->name('equipment.show');

    Route::get('departments', [DepartmentController::class, 'index'])
        ->middleware('can:departments.view')
        ->name('departments.index');
    Route::get('departments/{department}', [DepartmentController::class, 'show'])
        ->middleware('can:departments.view')
        ->name('departments.show');
});

// Putting a new colleague on the books.
Route::post('employees', [EmployeeController::class, 'store'])
    ->middleware(['auth', 'can:employees.manage'])
    ->name('employees.store');

Route::middleware(['auth', 'can:employees.manage'])->prefix('employees/{employee}')->name('employees.')->group(function () {
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
});

// Moving somebody, letting them go and taking them back: a right of its own,
// not a part of editing a card — HR moves people about without rewriting them.
Route::middleware(['auth', 'can:employees.status'])->prefix('employees/{employee}')->name('employees.')->group(function () {
    Route::post('transfer', [EmployeeStatusController::class, 'transfer'])->name('transfer');
    Route::post('fire', [EmployeeStatusController::class, 'fire'])->name('fire');
    Route::post('restore', [EmployeeStatusController::class, 'restore'])->name('restore');
});

// Striking the card out of the system altogether, with everything on it.
Route::delete('employees/{employee}', [EmployeeStatusController::class, 'destroy'])
    ->middleware(['auth', 'can:employees.delete'])
    ->name('employees.destroy');

// Putting a new unit on the books.
Route::post('equipment', [EquipmentController::class, 'store'])
    ->middleware(['auth', 'can:equipment.manage'])
    ->name('equipment.store');

// A unit's life: handed out, taken back, written off.
Route::middleware(['auth', 'can:equipment.manage'])->prefix('equipment/{equipment}')->name('equipment.')->group(function () {
    Route::post('issue', [EquipmentStatusController::class, 'issue'])->name('issue');
    Route::post('take', [EquipmentStatusController::class, 'take'])->name('take');
    Route::post('write-off', [EquipmentStatusController::class, 'writeOff'])->name('write-off');
});

// Struck off the books: for a duplicate or a mistake, not for wear, which is
// why it is a right of its own rather than a part of managing the fleet.
Route::delete('equipment/{equipment}', [EquipmentController::class, 'destroy'])
    ->middleware(['auth', 'can:equipment.delete'])
    ->name('equipment.destroy');

// The card and its service records, edited one block at a time.
Route::middleware(['auth', 'can:equipment.manage'])->prefix('equipment/{equipment}')->name('equipment.')->group(function () {
    Route::put('specs', [EquipmentDetailsController::class, 'specs'])->name('specs');
    Route::put('accessories', [EquipmentDetailsController::class, 'accessories'])->name('accessories');
    Route::put('state', [EquipmentDetailsController::class, 'state'])->name('state');

    // What has been done to it.
    Route::post('repairs', [EquipmentRepairController::class, 'store'])->name('repairs.store');
    Route::put('repairs/{repair}', [EquipmentRepairController::class, 'update'])->name('repairs.update');
    Route::delete('repairs/{repair}', [EquipmentRepairController::class, 'destroy'])->name('repairs.destroy');
});

// Directories: roles ("Позиция"), positions ("Должность"), departments, languages and equipment categories.
Route::middleware(['auth'])->prefix('directories')->name('directories.')->group(function () {
    Route::redirect('/', '/directories/roles');

    // Reading the lists is one right; adding to them and renaming is another.
    Route::middleware('can:directories.view')->group(function () {
        Route::resource('roles', Directories\RoleController::class)->only(['index']);
        Route::resource('positions', Directories\PositionController::class)->only(['index']);
        Route::resource('departments', Directories\DepartmentController::class)->only(['index']);
        Route::resource('languages', Directories\LanguageController::class)->only(['index']);
        Route::resource('equipment', Directories\EquipmentTypeController::class)->only(['index']);
    });

    Route::middleware('can:directories.manage')->group(function () {
        Route::resource('roles', Directories\RoleController::class)->only(['store', 'update', 'destroy']);
        Route::resource('positions', Directories\PositionController::class)->only(['store', 'update', 'destroy']);
        Route::resource('departments', Directories\DepartmentController::class)->only(['store', 'update', 'destroy']);
        Route::resource('languages', Directories\LanguageController::class)->only(['store', 'update', 'destroy']);
        Route::resource('equipment', Directories\EquipmentTypeController::class)->only(['store', 'update', 'destroy']);
    });

    // Who may do what. Not a right that can be handed out: only a system
    // administrator decides on access, so the middleware names the role.
    Route::middleware('role:sysadmin')->group(function () {
        Route::get('access', [Directories\AccessController::class, 'index'])->name('access.index');
        Route::put('access/{role}', [Directories\AccessController::class, 'update'])->name('access.update');
    });
});

// A right given to, or taken from, one colleague in particular.
Route::put('employees/{employee}/access', EmployeeAccessController::class)
    ->middleware(['auth', 'role:sysadmin'])
    ->name('employees.access');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
