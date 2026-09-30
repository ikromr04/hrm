import AppLayout from '@/layouts/app-layout';
import { directoryLists, useCan } from '@/lib/access';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { type ReactNode } from 'react';

/** Shell for the admin directories: title, tabs, then the current list. */
export default function DirectoriesLayout({ title, children }: { title: string; children: ReactNode }) {
    const { url } = usePage<SharedData>();
    const can = useCan();

    // Each list is a right of its own, «Доступы» included, so the strip carries
    // only the ones this person may open — a tab that led to a refusal would be
    // worse than no tab.
    const tabs = directoryLists.filter((list) => can(list.view)).map((list) => ({ title: list.title, href: `/directories/${list.key}` }));

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Справочники', href: '/directories' },
        { title, href: url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs} fitViewport>
            <Head title={`${title} — справочники`} />

            <div className="flex flex-1 flex-col gap-4 p-3 md:min-h-0 md:px-5 md:py-4">
                <h1 className="text-xl font-semibold tracking-tight">Справочники</h1>

                {/*
                 * One tab is not a choice: with a single list open the strip only
                 * repeats the heading and the breadcrumb, so it is left out and
                 * the list starts right under the title.
                 */}
                {tabs.length > 1 && (
                    <nav aria-label="Справочники" className="flex gap-6 border-b">
                        {tabs.map((tab) => {
                            const active = url.startsWith(tab.href);

                            return (
                                <Link
                                    key={tab.href}
                                    href={tab.href}
                                    prefetch
                                    aria-current={active ? 'page' : undefined}
                                    className={cn(
                                        '-mb-px border-b-2 px-1 pb-2.5 text-sm transition-colors',
                                        active
                                            ? 'border-brand text-foreground font-semibold'
                                            : 'text-muted-foreground hover:text-foreground border-transparent font-medium',
                                    )}
                                >
                                    {tab.title}
                                </Link>
                            );
                        })}
                    </nav>
                )}

                {children}
            </div>
        </AppLayout>
    );
}
