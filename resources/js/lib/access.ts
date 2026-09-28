import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

/**
 * Who may do what.
 *
 * Rights travel with a position and can be given to, or taken from, one person
 * on their card. The list below mirrors App\Support\Access, which is where
 * rights are actually declared; keeping the keys here as a type means a page
 * that asks for a right that no longer exists stops compiling.
 */
export type Permission =
    | 'employees.view'
    | 'employees.manage'
    | 'employees.private'
    | 'employees.status'
    | 'employees.delete'
    | 'equipment.view'
    | 'equipment.manage'
    | 'equipment.journal'
    | 'equipment.delete'
    | 'departments.view'
    | 'directories.view'
    | 'directories.manage';

/**
 * What a position carries the moment it is created: everybody who works here may
 * look around. Mirrors Access::DEFAULTS.
 */
export const accessDefaults: Permission[] = ['employees.view', 'equipment.view', 'departments.view'];

/** A right as the server describes it: what it is called and what it opens. */
export interface AccessRight {
    key: string;
    title: string;
    hint: string;
}

/** The rights of one section, the way the access table groups them. */
export interface AccessSection {
    key: string;
    title: string;
    rights: AccessRight[];
}

/** Whether the viewer holds a right, as the shared props report it. */
export function useCan(): (permission: Permission) => boolean {
    const { auth } = usePage<SharedData>().props;

    return (permission) => auth.can[permission] === true;
}

/**
 * Two roles are not ordinary ones: an administrator can do everything here, and
 * a system administrator decides who gets to. The server refuses the rest, and
 * these helpers keep the forms from offering what it would refuse.
 */

/** The roles that carry access to the whole system rather than naming a job. */
export const accessRoles = ['sysadmin', 'admin'];

export const accessNotice = 'Доступы администратора меняет только системный администратор.';

/** Whether a set of roles carries access, so its owner's card is off limits. */
export function holdsAccess(roles: string[]): boolean {
    return roles.some((role) => accessRoles.includes(role));
}

/** The roles a viewer may put on somebody's card. */
export function grantableRoles<T extends { name: string }>(roles: T[], manageAccess: boolean): T[] {
    return manageAccess ? roles : roles.filter((role) => !accessRoles.includes(role.name));
}
