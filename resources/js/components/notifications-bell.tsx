import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Skeleton } from '@/components/ui/skeleton';
import { formatDate } from '@/lib/employee';
import { formatMoment } from '@/lib/equipment';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Bell, ClipboardCheck, IdCard, type LucideIcon, PackageCheck, PackageOpen, PackageX, UserPlus } from 'lucide-react';
import { type MouseEvent, type ReactNode, useCallback, useEffect, useState, useSyncExternalStore } from 'react';

interface Notice {
    id: string;
    kind: string | null;
    text: string;
    /** The day a reminder was about, for the kinds that have one. */
    due: string | null;
    /** Where the line leads; absent when its reader may not open that card. */
    href: string | null;
    read: boolean;
    created_at: string | null;
}

const icons: Record<string, LucideIcon> = {
    'equipment.issued': PackageCheck,
    'equipment.taken': PackageOpen,
    'equipment.written_off': PackageX,
    'equipment.inventory': ClipboardCheck,
    'employee.placement': IdCard,
    'employee.added': UserPlus,
};

/** The same width at which a dialog turns into a bottom sheet (components/ui/dialog.tsx). */
const PHONE = '(max-width: 639px)';

function usePhone(): boolean {
    return useSyncExternalStore(
        (onChange) => {
            const query = window.matchMedia(PHONE);
            query.addEventListener('change', onChange);
            return () => query.removeEventListener('change', onChange);
        },
        () => window.matchMedia(PHONE).matches,
        () => false,
    );
}

/** Laravel's own cookie, sent back as the header it checks: these requests are not Inertia's. */
function post(url: string): Promise<Response> {
    const token = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/)?.[1];

    return fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(token ? { 'X-XSRF-TOKEN': decodeURIComponent(token) } : {}),
        },
    });
}

/** "только что", "5 мин назад", "вчера", and from there on the date the way the app writes dates. */
function when(iso: string | null): string {
    if (!iso) return '';

    const at = new Date(iso);
    const minutes = Math.floor((Date.now() - at.getTime()) / 60000);

    if (minutes < 1) return 'только что';
    if (minutes < 60) return `${minutes} мин назад`;

    const startOfToday = new Date().setHours(0, 0, 0, 0);

    if (at.getTime() >= startOfToday) return `${Math.floor(minutes / 60)} ч назад`;
    if (at.getTime() >= startOfToday - 86400000) return 'вчера';

    return formatMoment(iso);
}

/**
 * The bell in the header: a count of what has not been read, and behind it the
 * list. Every page carries only the count; the list is fetched when the bell is
 * pressed, so a page nobody opens the bell on pays nothing for it.
 */
export function NotificationsBell() {
    const unread = usePage<SharedData>().props.notifications?.unread ?? 0;
    const phone = usePhone();
    const [open, setOpen] = useState(false);
    const [items, setItems] = useState<Notice[] | null>(null);

    // The badge is the server's count, so after anything that changes it the
    // one prop is asked for again rather than a second copy kept here.
    const recount = useCallback(() => router.reload({ only: ['notifications'] }), []);

    useEffect(() => {
        if (!open) return;

        const controller = new AbortController();

        fetch(route('notifications.index'), { headers: { Accept: 'application/json' }, signal: controller.signal })
            .then((response) => (response.ok ? response.json() : Promise.reject(response)))
            .then((data: { unread: number; items: Notice[] }) => {
                setItems(data.items);
                // Something arrived since the page was drawn.
                if (data.unread !== unread) recount();
            })
            .catch(() => {
                if (!controller.signal.aborted) setItems((current) => current ?? []);
            });

        return () => controller.abort();
        // Fetched when the bell opens; the count moving while it is open is not a reason to fetch again.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]);

    const readAll = () => {
        setItems((current) => current?.map((item) => ({ ...item, read: true })) ?? null);
        post(route('notifications.read-all')).finally(recount);
    };

    const read = (item: Notice, event: MouseEvent) => {
        // A line that leads nowhere is read by being pressed.
        if (!item.href) {
            setItems((current) => current?.map((other) => (other.id === item.id ? { ...other, read: true } : other)) ?? null);
            post(route('notifications.read', item.id)).finally(recount);
            return;
        }

        const marked = item.read ? Promise.resolve() : post(route('notifications.read', item.id)).catch(() => undefined);

        // Opened in a new tab: the browser follows the link itself, this page stays.
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) {
            marked.finally(recount);
            return;
        }

        // The card is opened once the line is marked, so the page that draws
        // the card already counts it as read.
        event.preventDefault();
        setOpen(false);
        marked.finally(() => router.visit(item.href!));
    };

    // The list is only the latest lines, so the count has a say as well: there
    // may be unread ones further back than it reaches.
    const hasUnread = unread > 0 || !!items?.some((item) => !item.read);

    const trigger = (
        <Button
            variant="outline"
            size="icon"
            className="max-md:text-muted-foreground relative size-10 shrink-0 max-md:border-0 max-md:bg-transparent max-md:shadow-none md:size-9"
            aria-label={unread > 0 ? `Уведомления, непрочитанных: ${unread}` : 'Уведомления'}
            onClick={phone ? () => setOpen(true) : undefined}
        >
            <Bell className="size-5 md:size-4" />
            {unread > 0 && (
                <span
                    aria-hidden="true"
                    className="bg-brand text-brand-ink ring-background absolute top-0.5 right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full px-1 text-[10px] leading-none font-semibold ring-2 md:-top-1 md:-right-1"
                >
                    {unread > 99 ? '99+' : unread}
                </span>
            )}
        </Button>
    );

    const readAllButton = hasUnread && (
        <button
            type="button"
            onClick={readAll}
            className="text-brand-strong -mr-2 rounded-md px-2 text-sm font-medium hover:underline max-sm:h-10 dark:text-[#C5E27A]"
        >
            Прочитать все
        </button>
    );

    const list = <NoticeList items={items} onRead={read} />;

    if (phone) {
        return (
            <>
                {trigger}
                <Dialog open={open} onOpenChange={setOpen}>
                    <DialogContent className="gap-2 px-0">
                        <DialogHeader className="flex-row items-center justify-between space-y-0 px-4 pr-14">
                            <DialogTitle>Уведомления</DialogTitle>
                            <DialogDescription className="sr-only">Что произошло без вас</DialogDescription>
                            {readAllButton}
                        </DialogHeader>
                        {list}
                    </DialogContent>
                </Dialog>
            </>
        );
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>{trigger}</PopoverTrigger>
            <PopoverContent align="end" className="w-96 p-0">
                <div className="flex h-12 items-center justify-between border-b px-4">
                    <span className="text-sm font-semibold">Уведомления</span>
                    {readAllButton}
                </div>
                <div className="max-h-[min(26rem,calc(100dvh-8rem))] overflow-y-auto">{list}</div>
            </PopoverContent>
        </Popover>
    );
}

function NoticeList({ items, onRead }: { items: Notice[] | null; onRead: (item: Notice, event: MouseEvent) => void }) {
    if (items === null) {
        return (
            <div className="space-y-4 p-4" aria-busy="true">
                {[0, 1, 2].map((row) => (
                    <div key={row} className="flex gap-3">
                        <Skeleton className="size-9 shrink-0 rounded-md" />
                        <div className="flex-1 space-y-2">
                            <Skeleton className="h-4 w-full" />
                            <Skeleton className="h-3 w-24" />
                        </div>
                    </div>
                ))}
            </div>
        );
    }

    if (items.length === 0) {
        return <p className="text-muted-foreground px-4 py-10 text-center text-sm">Пока ничего нового</p>;
    }

    return (
        <ul className="divide-y">
            {items.map((item) => (
                <li key={item.id}>
                    <Row item={item} onRead={onRead} />
                </li>
            ))}
        </ul>
    );
}

function Row({ item, onRead }: { item: Notice; onRead: (item: Notice, event: MouseEvent) => void }) {
    const Icon = icons[item.kind ?? ''] ?? Bell;
    const className = 'flex w-full items-start gap-3 px-4 py-3 text-left';
    const pressable = 'hover:bg-accent/60 active:bg-accent focus-visible:bg-accent/60 outline-hidden transition-colors';

    const body: ReactNode = (
        <>
            <span
                className={cn(
                    'flex size-9 shrink-0 items-center justify-center rounded-md',
                    item.read ? 'bg-muted text-muted-foreground' : 'bg-brand-soft text-brand-strong dark:bg-white/10 dark:text-[#C5E27A]',
                )}
            >
                <Icon className="size-4" />
            </span>
            <span className="min-w-0 flex-1">
                <span className={cn('block text-sm leading-snug break-words', item.read ? 'text-muted-foreground' : 'text-foreground font-medium')}>
                    {item.text}
                    {item.due && ` — срок ${formatDate(item.due)}`}
                </span>
                <span className="text-muted-foreground mt-1 block text-xs">{when(item.created_at)}</span>
            </span>
            {!item.read && <span className="bg-brand mt-1.5 size-2 shrink-0 rounded-full" aria-label="не прочитано" />}
        </>
    );

    if (item.href) {
        return (
            <a href={item.href} onClick={(event) => onRead(item, event)} className={cn(className, pressable)}>
                {body}
            </a>
        );
    }

    // Nothing to open: the line is the whole of it, and pressing an unread one
    // is how it is marked read.
    if (!item.read) {
        return (
            <button type="button" onClick={(event) => onRead(item, event)} className={cn(className, pressable)}>
                {body}
            </button>
        );
    }

    return <div className={className}>{body}</div>;
}
