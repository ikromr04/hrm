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
        // On a phone every pixel of the bar goes to the page's name: tighter
        // padding and gaps, and no divider, since the menu button is a full-size
        // target there rather than a small icon beside the trail.
        <header className="flex h-14 shrink-0 items-center gap-1 border-b px-3 md:gap-2 md:px-4">
            <SidebarTrigger className="-ml-1 h-9 w-9 md:h-7 md:w-7" aria-label="Свернуть меню" />
            <Separator orientation="vertical" className="mx-1 h-4! max-md:hidden" />
            <div className="min-w-0 flex-1 max-md:px-1">
                <Breadcrumbs breadcrumbs={[rootCrumb, ...breadcrumbs]} />
            </div>

            <GlobalSearch />

            <ThemeToggle />
            <Button variant="outline" size="icon" className="size-9 shrink-0" aria-label="Уведомления">
                <Bell />
            </Button>
        </header>
    );
}
