import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';

interface AppSidebarLayoutProps {
    children: React.ReactNode;
    breadcrumbs?: BreadcrumbItem[];
    /** Lock the content to the window height on desktop so the page itself never scrolls; the page scrolls its own panes. */
    fitViewport?: boolean;
}

export default function AppSidebarLayout({ children, breadcrumbs = [], fitViewport = false }: AppSidebarLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className={cn(fitViewport && 'md:h-[calc(100svh-(--spacing(4)))] md:overflow-hidden')}>
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
        </AppShell>
    );
}
