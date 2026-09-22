import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    path: string;
}

/** Page numbers to show: 1 2 3 … 28, keeping neighbours of the current page. */
function pageList(current: number, last: number): (number | 'gap')[] {
    const pages = new Set([1, last, current - 1, current, current + 1].filter((p) => p >= 1 && p <= last));
    if (current <= 3) [2, 3].forEach((p) => p <= last && pages.add(p));

    const sorted = [...pages].sort((a, b) => a - b);
    return sorted.flatMap((page, i) => (i > 0 && page - sorted[i - 1] > 1 ? ['gap' as const, page] : [page]));
}

const pageButton = 'size-9 rounded-md border font-semibold';

export function Pagination({ paginator, className }: { paginator: Paginated<unknown>; className?: string }) {
    const { current_page: current, last_page: last } = paginator;
    const url = (page: number) => {
        const query = new URLSearchParams(window.location.search);
        query.set('page', String(page));
        return `${paginator.path}?${query.toString()}`;
    };

    const arrow = (page: number, label: string, icon: React.ReactNode, disabled: boolean) =>
        disabled ? (
            <span aria-disabled="true" className={cn(buttonVariants({ variant: 'outline', size: 'icon' }), 'size-9 opacity-50')} aria-label={label}>
                {icon}
            </span>
        ) : (
            <Link href={url(page)} preserveScroll className={cn(buttonVariants({ variant: 'outline', size: 'icon' }), 'size-9')} aria-label={label}>
                {icon}
            </Link>
        );

    return (
        <nav aria-label="Страницы" className={cn('text-muted-foreground flex items-center gap-2 text-sm', className)}>
            <span className="flex-1">
                Показано {paginator.data.length} из {paginator.total}
            </span>
            {last > 1 && (
                <>
                    {arrow(current - 1, 'Предыдущая страница', <ChevronLeft />, current <= 1)}
                    {pageList(current, last).map((page, i) =>
                        page === 'gap' ? (
                            <span key={`gap-${i}`} aria-hidden="true">
                                …
                            </span>
                        ) : (
                            <Link
                                key={page}
                                href={url(page)}
                                preserveScroll
                                aria-current={page === current ? 'page' : undefined}
                                className={cn(
                                    buttonVariants({ variant: page === current ? 'default' : 'outline', size: 'icon' }),
                                    pageButton,
                                    page === current ? 'border-transparent' : 'text-foreground',
                                )}
                            >
                                {page}
                            </Link>
                        ),
                    )}
                    {arrow(current + 1, 'Следующая страница', <ChevronRight />, current >= last)}
                </>
            )}
        </nav>
    );
}
