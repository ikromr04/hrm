import { Breadcrumbs } from '@/components/breadcrumbs';
import { ThemeToggle } from '@/components/theme-toggle';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { Bell, Search } from 'lucide-react';

const rootCrumb: BreadcrumbItemType = { title: 'Evolet HRM', href: '/dashboard' };

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    return (
        <header className="flex h-14 shrink-0 items-center gap-2 border-b px-4">
            <SidebarTrigger className="-ml-1" aria-label="Свернуть меню" />
            <Separator orientation="vertical" className="mx-1 h-4!" />
            <div className="min-w-0 flex-1">
                <Breadcrumbs breadcrumbs={[rootCrumb, ...breadcrumbs]} />
            </div>

            <label className="border-input bg-background text-muted-foreground focus-within:ring-ring hidden h-9 w-72 items-center gap-2 rounded-md border px-3 shadow-xs focus-within:ring-2 md:flex">
                <Search className="size-4 shrink-0" />
                <span className="sr-only">Поиск</span>
                <input type="search" placeholder="Поиск…" className="text-foreground min-w-0 flex-1 bg-transparent text-sm outline-hidden" />
            </label>

            <ThemeToggle />
            <Button variant="outline" size="icon" className="size-9" aria-label="Уведомления">
                <Bell />
            </Button>
        </header>
    );
}
