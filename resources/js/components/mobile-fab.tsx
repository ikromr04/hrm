import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { Plus, type LucideIcon } from 'lucide-react';

/**
 * The page's main action on a phone: a round button over the bottom right
 * corner, just above the tab bar, where the thumb already is. From `md` up the
 * page shows its ordinary button instead, so the page draws both and each
 * hides itself at the other size.
 */
export function MobileFab({
    href,
    onClick,
    label,
    icon: Icon = Plus,
    className,
}: {
    href?: string;
    onClick?: () => void;
    /** Read out by a screen reader; the button itself shows only the icon. */
    label: string;
    icon?: LucideIcon;
    className?: string;
}) {
    const classes = cn(
        'bg-primary text-primary-foreground fixed right-4 bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-30 flex size-14 items-center justify-center rounded-full shadow-lg shadow-black/20 transition-transform active:scale-95 md:hidden',
        className,
    );
    const icon = <Icon className="size-6" strokeWidth={2.25} aria-hidden="true" />;

    return href ? (
        <Link href={href} prefetch className={classes} aria-label={label}>
            {icon}
        </Link>
    ) : (
        <button type="button" onClick={onClick} className={classes} aria-label={label}>
            {icon}
        </button>
    );
}
