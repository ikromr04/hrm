import { Breadcrumbs } from '@/components/breadcrumbs';
import { GlobalSearch } from '@/components/global-search';
import { ThemeToggle } from '@/components/theme-toggle';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { Bell } from 'lucide-react';

const rootCrumb: BreadcrumbItemType = { title: 'Evolet HRM', href: '/dashboard' };

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    return (
        <header className="flex h-14 shrink-0 items-center gap-2 border-b px-4">
            <SidebarTrigger className="-ml-1" aria-label="Свернуть меню" />
            <Separator orientation="vertical" className="mx-1 h-4!" />
            <div className="min-w-0 flex-1">
                <Breadcrumbs breadcrumbs={[rootCrumb, ...breadcrumbs]} />
            </div>

            <GlobalSearch />

            <ThemeToggle />
            <Button variant="outline" size="icon" className="size-9" aria-label="Уведомления">
                <Bell />
            </Button>
        </header>
    );
}
