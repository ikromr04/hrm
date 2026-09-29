<?php

namespace App\Providers;

use App\Models\User;
use App\Support\EmployeeFields;
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
        // a position or given to one person on their card. Spatie answers "can"
        // for those out of the permissions table, and a personal exception is
        // applied in User::hasPermissionTo().

        // The exception is a form that saves a whole block of a card: it asks one
        // question — "may this person change anything in here?" — which is an
        // answer derived from the lines of that block rather than a right of its
        // own. Defined so a route can name it.
        foreach (array_keys(EmployeeFields::GROUPS) as $group) {
            Gate::define(
                EmployeeFields::blockGate($group),
                // editableBy() answers both halves of the question: nobody retypes
                // what they cannot read, so the right to change one line counts
                // only where the right to read it is there too.
                fn (User $user) => array_intersect(EmployeeFields::ofGroup($group), EmployeeFields::editableBy($user)) !== [],
            );
        }

        // Adding a colleague, and their photograph, which belongs to no block:
        // whoever may change a card may start one.
        Gate::define('employees.edit.any', fn (User $user) => EmployeeFields::editableBy($user) !== []);
    }
}
