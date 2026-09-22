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
    }
}
