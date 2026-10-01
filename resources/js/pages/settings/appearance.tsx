import { Head } from '@inertiajs/react';
import { Check, Monitor, Moon, Sun, type LucideIcon } from 'lucide-react';

import AppearanceTabs from '@/components/appearance-tabs';
import HeadingSmall from '@/components/heading-small';
import { useAppearance, type Appearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Оформление',
        href: '/settings/appearance',
    },
];

const options: { value: Appearance; icon: LucideIcon; label: string }[] = [
    { value: 'light', icon: Sun, label: 'Светлая' },
    { value: 'dark', icon: Moon, label: 'Тёмная' },
    { value: 'system', icon: Monitor, label: 'Как в системе' },
];

/**
 * The choice as a phone's settings app draws it: a grouped card of rows, the
 * chosen one ticked. A segmented control of three long words does not fit a
 * narrow screen, and a list is what a thumb expects there anyway.
 */
function PhoneAppearanceList() {
    const { appearance, updateAppearance } = useAppearance();

    return (
        <div className="flex flex-col gap-1.5 md:hidden">
            <ul role="radiogroup" aria-label="Тема" className="bg-card divide-border/60 divide-y overflow-hidden rounded-2xl">
                {options.map(({ value, icon: Icon, label }) => (
                    <li key={value}>
                        <button
                            type="button"
                            role="radio"
                            aria-checked={appearance === value}
                            onClick={() => updateAppearance(value)}
                            className="active:bg-accent/60 flex min-h-12 w-full items-center gap-3 px-4 text-left text-[15px]"
                        >
                            <Icon className="text-muted-foreground size-5 shrink-0" />
                            <span className="flex-1">{label}</span>
                            <Check
                                className={cn('text-brand-strong size-5 shrink-0 dark:text-[#C5E27A]', appearance !== value && 'invisible')}
                                aria-hidden="true"
                            />
                        </button>
                    </li>
                ))}
            </ul>
            <p className="text-muted-foreground px-1 text-[13px]">Выбор сохраняется в этом браузере.</p>
        </div>
    );
}

export default function Appearance() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Оформление" />

            <SettingsLayout>
                <div className="space-y-6 max-md:hidden">
                    <HeadingSmall title="Оформление" description="Светлая или тёмная тема — выбор сохраняется в этом браузере" />
                    <AppearanceTabs />
                </div>

                <PhoneAppearanceList />
            </SettingsLayout>
        </AppLayout>
    );
}
