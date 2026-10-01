import { Breadcrumbs } from '@/components/breadcrumbs';
import { GlobalSearch } from '@/components/global-search';
import { ThemeToggle } from '@/components/theme-toggle';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { Link } from '@inertiajs/react';
import { Bell, ChevronLeft } from 'lucide-react';

const rootCrumb: BreadcrumbItemType = { title: 'Evolet HRM', href: '/dashboard' };

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    // A phone has the tab bar for the sections, so its top bar is an app's:
    // a way back to the parent page, the name of this one and two icons.
    const parent = breadcrumbs.length > 1 ? breadcrumbs[breadcrumbs.length - 2] : null;
    const title = breadcrumbs[breadcrumbs.length - 1]?.title ?? 'Evolet HRM';

    return (
        <header className="max-md:bg-background/90 max-md:supports-[backdrop-filter]:bg-background/75 flex h-12 shrink-0 items-center gap-1 border-b px-2 max-md:sticky max-md:top-0 max-md:z-30 max-md:pt-[env(safe-area-inset-top)] max-md:backdrop-blur-xl md:h-14 md:gap-2 md:px-4">
            <SidebarTrigger className="-ml-1 h-7 w-7 max-md:hidden" aria-label="Свернуть меню" />
            <Separator orientation="vertical" className="mx-1 h-4! max-md:hidden" />
            <div className="min-w-0 flex-1 max-md:hidden">
                <Breadcrumbs breadcrumbs={[rootCrumb, ...breadcrumbs]} />
            </div>

            <div className="flex min-w-0 flex-1 items-center md:hidden">
                {parent ? (
                    <Link
                        href={parent.href}
                        prefetch
                        className="text-brand-strong active:bg-accent -ml-1 flex size-10 shrink-0 items-center justify-center rounded-full dark:text-[#C5E27A]"
                        aria-label={`Назад: ${parent.title}`}
                    >
                        <ChevronLeft className="size-6" />
                    </Link>
                ) : (
                    <span className="w-2 shrink-0" />
                )}
                <span className="truncate text-[17px] font-semibold tracking-tight">{title}</span>
            </div>

            <GlobalSearch />

            <div className="max-md:hidden">
                <ThemeToggle />
            </div>
            <Button
                variant="outline"
                size="icon"
                className="max-md:text-muted-foreground size-10 shrink-0 max-md:border-0 max-md:bg-transparent max-md:shadow-none md:size-9"
                aria-label="Уведомления"
            >
                <Bell className="size-5 md:size-4" />
            </Button>
        </header>
    );
}
