import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type SharedData, type SidebarNavGroup } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BarChart3, Bell, BookMarked, Briefcase, CalendarDays, Laptop, LayoutGrid, Settings, Target, Users, Wallet } from 'lucide-react';
import AppLogo from './app-logo';

const navGroups: SidebarNavGroup[] = [
    {
        items: [
            { title: 'Главная', url: '/dashboard', icon: LayoutGrid },
            { title: 'Сотрудники', url: '/employees', icon: Users },
            { title: 'Оборудование', icon: Laptop },
            { title: 'Отпуска', icon: CalendarDays },
        ],
    },
    {
        title: 'Процессы',
        items: [
            { title: 'Найм', icon: Briefcase },
            { title: 'Зарплата', icon: Wallet },
            { title: 'Оценка', icon: Target },
            { title: 'Отчёты', icon: BarChart3 },
        ],
    },
];

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;

    const footerGroup: SidebarNavGroup = {
        items: [
            // Only shown to people who may edit positions, roles and departments.
            ...(auth.can.manageDirectories ? [{ title: 'Справочники', url: '/directories', icon: BookMarked }] : []),
            { title: 'Настройки', url: '/settings', icon: Settings },
            { title: 'Уведомления', icon: Bell },
        ],
    };

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            asChild
                            className="h-14 group-data-[collapsible=icon]:p-1! hover:bg-transparent active:bg-transparent"
                        >
                            <Link href="/dashboard" prefetch aria-label="Evolet HRM — на главную">
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {navGroups.map((group) => (
                    <NavMain key={group.items[0].title} group={group} />
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavMain group={footerGroup} className="p-0" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
