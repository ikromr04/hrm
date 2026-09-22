import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type SidebarNavGroup } from '@/types';
import { Link, usePage } from '@inertiajs/react';

const activeClasses =
    'data-[active=true]:bg-brand-soft data-[active=true]:text-foreground data-[active=true]:[&>svg]:text-brand-strong dark:data-[active=true]:bg-sidebar-accent';

export function NavMain({ group, className }: { group: SidebarNavGroup; className?: string }) {
    const page = usePage();

    return (
        <SidebarGroup className={className ?? 'px-2 py-0'}>
            {group.title && <SidebarGroupLabel>{group.title}</SidebarGroupLabel>}
            <SidebarMenu>
                {group.items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        {item.url ? (
                            <SidebarMenuButton asChild isActive={page.url.startsWith(item.url)} tooltip={item.title} className={activeClasses}>
                                <Link href={item.url} prefetch>
                                    <item.icon />
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        ) : (
                            <SidebarMenuButton aria-disabled>
                                <item.icon />
                                <span>{item.title}</span>
                            </SidebarMenuButton>
                        )}
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
