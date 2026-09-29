<?php

namespace App\Policies;

use App\Models\User;
use App\Support\EmployeeFields;

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
     * Whether anything kept beside the account — passport, birth date, address,
     * telephone, family, hire date — is readable at all, which decides whether
     * the row behind it is worth loading.
     *
     * Everybody reads their own card whole. Beyond that it is field by field,
     * and this only asks whether at least one of those fields is open.
     */
    public function viewPrivateDetails(User $viewer, User $employee): bool
    {
        return EmployeeFields::anyPrivateVisibleTo($viewer, $employee);
    }

    /**
     * The same question about the staff at large, which is what the directory
     * asks before it offers to sort or filter by such a field.
     */
    public function viewAnyPrivateDetails(User $viewer): bool
    {
        return EmployeeFields::anyPrivateVisibleTo($viewer);
    }
}
