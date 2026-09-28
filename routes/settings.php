<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/email', [ProfileController::class, 'cancel'])->name('email.cancel');

    // The link from the letter: signed, so only what we sent can change an
    // address, and short-lived, so an old letter cannot.
    Route::get('settings/email/{user}/{hash}', [ProfileController::class, 'confirm'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('email.confirm');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance');
});
