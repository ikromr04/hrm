import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { FlashNotice } from '@/components/flash-notice';
import { MobileTabBar } from '@/components/mobile-tab-bar';
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
            {/* On a phone the tab bar sits over the bottom of the page; the padding keeps the last row clear of it. */}
            <AppContent
                variant="sidebar"
                className={cn(
                    'max-md:bg-muted/40 max-md:pb-[calc(3.5rem+env(safe-area-inset-bottom))]',
                    fitViewport && 'md:h-[calc(100svh-(--spacing(4)))] md:overflow-hidden',
                )}
            >
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                <FlashNotice />
                {children}
            </AppContent>
            <MobileTabBar />
        </AppShell>
    );
}
