<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Private details: passport, birth date, address, phones, family, hire date.
     *
     * Admins pass through Gate::before in AppServiceProvider. HR and the direct
     * manager will be added here once the schema has them.
     */
    public function viewPrivateDetails(User $viewer, User $employee): bool
    {
        return $viewer->is($employee);
    }

    /**
     * Private details of every employee at once, e.g. to sort the directory
     * by them. Admins only for now (via Gate::before); HR will be added here.
     */
    public function viewAnyPrivateDetails(User $viewer): bool
    {
        return false;
    }
}
