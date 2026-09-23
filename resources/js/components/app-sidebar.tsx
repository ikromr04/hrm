import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type SharedData, type SidebarNavGroup } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BarChart3, BookMarked, Briefcase, CalendarDays, Laptop, LayoutGrid, Network, Target, Users, Wallet } from 'lucide-react';
import AppLogo from './app-logo';

const navGroups: SidebarNavGroup[] = [
    {
        items: [
            { title: 'Главная', url: '/dashboard', icon: LayoutGrid },
            { title: 'Сотрудники', url: '/employees', icon: Users },
            { title: 'Структура компании', url: '/departments', icon: Network },
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
        // Settings live under the avatar next to "Выйти", notifications behind
        // the bell in the header. Repeating either here would only mean two
        // doors to one room, and among company-wide entries settings would
        // read as something they are not.
        items: [
            // Only shown to people who may edit positions, roles and departments.
            ...(auth.can.manageDirectories ? [{ title: 'Справочники', url: '/directories', icon: BookMarked }] : []),
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
                {footerGroup.items.length > 0 && <NavMain group={footerGroup} className="p-0" />}
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
