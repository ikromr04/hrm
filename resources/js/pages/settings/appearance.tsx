import { Head } from '@inertiajs/react';

import AppearanceTabs from '@/components/appearance-tabs';
import HeadingSmall from '@/components/heading-small';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Оформление',
        href: '/settings/appearance',
    },
];

export default function Appearance() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Оформление" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Оформление" description="Светлая или тёмная тема — выбор сохраняется в этом браузере" />
                    {/* Three options side by side are wider than a phone, so there they stack. */}
                    <AppearanceTabs className="max-sm:flex max-sm:w-full max-sm:flex-col max-sm:*:py-2.5" />
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
