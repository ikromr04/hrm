<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Somebody's card. Reading the staff at large takes the right to; reading
     * your own takes nothing, because it is yours.
     */
    public function view(User $viewer, User $employee): bool
    {
        return $viewer->is($employee) || $viewer->can('employees.view');
    }

    /**
     * Private details: passport, birth date, address, phones, family, hire date.
     *
     * Everybody may read their own. Beyond that it takes the right to private
     * data; administrators pass through Gate::before in AppServiceProvider.
     */
    public function viewPrivateDetails(User $viewer, User $employee): bool
    {
        return $viewer->is($employee) || $viewer->can('employees.private');
    }

    /**
     * Private details of every employee at once, e.g. to sort the directory
     * by them — which is more than reading one card, and takes the same right.
     */
    public function viewAnyPrivateDetails(User $viewer): bool
    {
        return $viewer->can('employees.private');
    }
}
