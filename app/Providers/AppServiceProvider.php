<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
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
        // In a Codespace the application sits behind a proxy that speaks HTTPS to
        // the browser and plain HTTP to us, so links are written the way the page
        // is read rather than the way the request arrived.
        if (env('CODESPACES') === 'true') {
            URL::forceScheme('https');
        }

        // Administrators pass every authorization check, including rights added
        // later. A system administrator is one too; what only they can do —
        // appoint an administrator, or take the rights away — is guarded where
        // roles are assigned, since it is a rule about roles, not an ability.
        Gate::before(fn (User $user) => $user->hasAnyRole(['sysadmin', 'admin']) ? true : null);

        // Every other right is a permission from App\Support\Access, carried by
        // a position or given to one person on their card. Nothing to define
        // here: Spatie answers "can" for those out of the permissions table, and
        // a personal exception is applied in User::hasPermissionTo().
    }
}
