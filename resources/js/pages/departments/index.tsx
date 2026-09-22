import { OrgChart, type OrgDepartment } from '@/components/org-chart';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { peopleLabel } from '@/lib/employee';
import { plural } from '@/lib/plural';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Структура компании', href: '/departments' }];

export default function Departments({ departments, employees_count }: { departments: OrgDepartment[]; employees_count: number }) {
    return (
        // The chart scrolls inside its own frame.
        <AppLayout breadcrumbs={breadcrumbs} fitViewport>
            <Head title="Структура компании" />

            <div className="flex flex-1 flex-col gap-4 p-3 md:min-h-0 md:px-5 md:py-4">
                <div className="flex flex-col gap-1">
                    <h1 className="text-xl font-semibold tracking-tight">Структура компании</h1>
                    <p className="text-muted-foreground text-sm">
                        {departments.length} {plural(departments.length, ['подразделение', 'подразделения', 'подразделений'])} ·{' '}
                        {peopleLabel(employees_count)} в компании
                    </p>
                </div>

                {departments.length === 0 ? (
                    <Card className="text-muted-foreground rounded-xl p-12 text-center text-sm">Отделов пока нет.</Card>
                ) : (
                    <Card className="bg-sidebar flex flex-col gap-0 overflow-hidden rounded-xl p-0 md:min-h-0 md:flex-1">
                        <OrgChart departments={departments} employeesCount={employees_count} />
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
