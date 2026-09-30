<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Directories;
use App\Support\EmployeeFields;
use App\Support\EquipmentAccess;
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
                // editableBy() answers every part of the question: whose card this
                // is, which of its lines are readable — nobody retypes what they
                // cannot see — and which of those are theirs to change.
                fn (User $user, ?User $employee = null) => array_intersect(
                    EmployeeFields::ofGroup($group),
                    EmployeeFields::editableBy($user, $employee),
                ) !== [],
            );
        }

        // The equipment section is open to whoever sees any part of the fleet,
        // and its journal to whoever reads any part of that.
        Gate::define('equipment.view.any', fn (User $user) => EquipmentAccess::sees($user));
        Gate::define('equipment.journal.any', fn (User $user) => EquipmentAccess::readsJournal($user));

        // The directories are five lists behind one door: the door opens for
        // whoever may read any of them, and each list then answers for itself.
        Gate::define('directories.view.any', fn (User $user) => Directories::sees($user));

        // Changing a list takes reading it as well. The pair is a gate of its own
        // because the bare right would pass on its own — a permission answers
        // before any gate of the same name would.
        foreach (array_keys(Directories::LISTS) as $list) {
            Gate::define("directories.manage.{$list}", fn (User $user) => Directories::canEdit($user, $list));
        }

        // A photograph belongs to no block of the card, and adding a colleague is
        // editing a card that does not exist yet. Both ask the same question:
        // whoever may change something here may do this. Given the card, the
        // question is asked of that card — a person's own photograph is theirs.
        Gate::define('employees.edit.any', fn (User $user, ?User $employee = null) => EmployeeFields::editableBy($user, $employee) !== []);
    }
}
