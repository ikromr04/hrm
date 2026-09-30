<?php

namespace App\Http\Requests\Concerns;

use App\Models\User;
use Closure;
use Illuminate\Validation\Validator;

/**
 * Who may hand out access, and to whom.
 *
 * Two roles are not ordinary ones: an administrator can do everything in the
 * system, and a system administrator decides who gets to. So an administrator
 * cannot appoint another one, cannot take the rights away from one, and cannot
 * touch the roles of anybody who already holds them — otherwise any admin could
 * quietly promote themselves a colleague or strip the person who appointed them.
 * Only a system administrator settles that side of things.
 */
trait GuardsPrivilegedRoles
{
    /**
     * The roles that carry access to the whole system rather than naming a job.
     *
     * @var list<string>
     */
    public const PRIVILEGED = ['sysadmin', 'admin'];

    /**
     * Handing one of them out, checked as the value itself so the message lands
     * on the field the role was picked in.
     */
    protected function privilegedRoleGuard(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            if (! in_array($value, self::PRIVILEGED, true) || $this->bySysadmin()) {
                return;
            }

            // Leaving a role somebody already holds in the list is not handing it
            // out, or an administrator could not so much as fix a colleague's
            // surname on a card that happens to carry access.
            $employee = $this->route('employee');

            if ($employee instanceof User && $employee->hasRole($value)) {
                return;
            }

            $fail('Назначать администратора может только системный администратор.');
        };
    }

    /**
     * Changing the roles of somebody who already has access, and taking access
     * away. Both are read from the list the form sent against what the person
     * holds now, which rules cannot see one value at a time.
     */
    protected function guardPrivilegedChanges(Validator $validator, User $employee): void
    {
        $asked = array_values((array) $this->input('roles', []));
        $held = $employee->roles->pluck('name')->all();

        sort($asked);
        sort($held);

        if ($asked === $held) {
            return;
        }

        $privileged = array_intersect(self::PRIVILEGED, $held) !== [];

        if ($privileged && ! $this->bySysadmin()) {
            $validator->errors()->add('roles', 'Менять доступы администратора может только системный администратор.');

            return;
        }

        // Nobody leaves the system without a way back in: the last hands that
        // can appoint an administrator are not the ones to be emptied.
        foreach (array_diff($held, $asked) as $lost) {
            if (in_array($lost, self::PRIVILEGED, true) && $employee->is($this->user())) {
                $validator->errors()->add('roles', 'Нельзя снять доступ с самого себя.');
            }
        }
    }

    /**
     * Whether the change is being made by a system administrator.
     *
     * Appointing an administrator is not the same duty as filling in the access
     * table, and it stays with the role rather than with a right: an
     * administrator holds every right there is, and could otherwise promote a
     * colleague or strip whoever appointed them.
     */
    private function bySysadmin(): bool
    {
        return (bool) $this->user()?->hasRole('sysadmin');
    }
}
