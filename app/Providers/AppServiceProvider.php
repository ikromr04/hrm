<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Admins pass every authorization check, including abilities added later.
        Gate::before(fn (User $user) => $user->hasRole('admin') ? true : null);

        // Editing positions, roles and departments. Admins only for now (via before); HR can be added here.
        Gate::define('manage-directories', fn (User $user) => false);

        // Transferring, firing, restoring and deleting employees; seeing who left.
        Gate::define('manage-employees', fn (User $user) => false);

        // Deciding on time off. A request goes to the head of the employee's
        // department first and to HR — admins, for now — after that, so anyone
        // who heads a department sees this side of the section at all.
        Gate::define('approve-leave', fn (User $user) => $user->departments()->wherePivot('is_head', true)->exists());
    }
}
