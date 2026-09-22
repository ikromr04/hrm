import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
    /** What the current user may do; mirrors server-side gates. */
    can: {
        manageDirectories: boolean;
    };
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SidebarNavItem {
    title: string;
    icon: LucideIcon;
    /** Omit for modules that are not built yet; the item renders as disabled. */
    url?: string;
}

export interface SidebarNavGroup {
    title?: string;
    items: SidebarNavItem[];
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    surname: string;
    patronymic: string | null;
    avatar: string | null;
    sex: 'male' | 'female';
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
