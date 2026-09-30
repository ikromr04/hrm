import { useCan } from '@/lib/access';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { type ReactNode } from 'react';

/**
 * A colleague's name, as a way to their card when there is one.
 *
 * Names appear all over the place — the structure of the company, the holder of a
 * laptop, whoever moved it — and a name is not a secret. Their card is: reading
 * somebody else's takes the right to the staff, so without it the name is simply
 * a name and does not pretend to lead anywhere. One's own card is the exception,
 * since it is open to everybody and lives at an address of its own.
 */
/** The same classes with every hover state dropped, including the dark-mode ones. */
const still = (className?: string): string | undefined =>
    className
        ?.split(' ')
        .filter((name) => !name.startsWith('hover:') && !name.startsWith('dark:hover:'))
        .join(' ');

export function PersonLink({ id, className, title, children }: { id: number; className?: string; title?: string; children: ReactNode }) {
    const { auth } = usePage<SharedData>().props;
    const can = useCan();
    const self = auth.user?.id === id;

    if (!self && !can('employees.view')) {
        // Plain text, and it has to look plain: the classes around these names
        // carry hover styling, which would go on promising a link that is not there.
        return <span className={still(className)}>{children}</span>;
    }

    return (
        <Link href={self ? route('profile') : route('employees.show', id)} className={className} title={title}>
            {children}
        </Link>
    );
}
