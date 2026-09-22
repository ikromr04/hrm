import { Pagination, type Paginated } from '@/components/pagination';
import { PersonAvatar } from '@/components/person-avatar';
import { StatusBadge, type StatusTone } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import { plural } from '@/lib/plural';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { format, parseISO } from 'date-fns';
import { ChevronDown, Download, Ellipsis, Plus } from 'lucide-react';

type Status = 'active' | 'probation' | 'leave' | 'dismissed';

interface EmployeeRow {
    id: number;
    name: string;
    email: string;
    position: string;
    department: string;
    status: Status;
    hired_at: string;
    location: string;
    manager: string | null;
}

interface Filters {
    status: Status | null;
    department: string | null;
    position: string | null;
    location: string | null;
}

interface EmployeesProps {
    employees: Paginated<EmployeeRow>;
    counts: Record<'all' | Status, number>;
    filters: Filters;
    options: { departments: string[]; positions: string[]; locations: string[] };
    summary: { people: number; departments: number };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Сотрудники', href: '/employees' }];

const statuses: Record<Status, { label: string; tone: StatusTone }> = {
    active: { label: 'Работает', tone: 'success' },
    probation: { label: 'Испытательный', tone: 'warning' },
    leave: { label: 'В отпуске', tone: 'info' },
    dismissed: { label: 'Уволен', tone: 'neutral' },
};

const tabs: { key: Status | null; label: string }[] = [
    { key: null, label: 'Все' },
    { key: 'active', label: 'Работают' },
    { key: 'probation', label: 'Испытательный' },
    { key: 'leave', label: 'В отпуске' },
    { key: 'dismissed', label: 'Уволенные' },
];

/** Drops empty values so the URL stays clean, and resets to page 1. */
function query(filters: Filters) {
    return Object.fromEntries(Object.entries(filters).filter(([, value]) => value));
}

function FilterMenu({
    label,
    value,
    options,
    onChange,
}: {
    label: string;
    value: string | null;
    options: string[];
    onChange: (value: string | null) => void;
}) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="outline" className={cn('h-9 font-normal', value && 'border-brand-strong/40 bg-brand-soft/60 dark:bg-white/10')}>
                    {label}: {value ?? 'все'}
                    <ChevronDown className="text-muted-foreground" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="max-h-80 min-w-56 overflow-y-auto">
                <DropdownMenuRadioGroup value={value ?? ''} onValueChange={(next) => onChange(next || null)}>
                    <DropdownMenuRadioItem value="">Все</DropdownMenuRadioItem>
                    <DropdownMenuSeparator />
                    {options.map((option) => (
                        <DropdownMenuRadioItem key={option} value={option}>
                            {option}
                        </DropdownMenuRadioItem>
                    ))}
                </DropdownMenuRadioGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

export default function Employees({ employees, counts, filters, options, summary }: EmployeesProps) {
    const apply = (changes: Partial<Filters>) => {
        router.get(route('employees.index'), query({ ...filters, ...changes }), { preserveState: true, preserveScroll: true, replace: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs} fitViewport>
            <Head title="Сотрудники" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:min-h-0 md:px-8 md:py-6">
                <div className="flex flex-wrap items-end gap-4">
                    <div className="flex flex-1 flex-col gap-1">
                        <h1 className="text-2xl font-semibold tracking-tight">Сотрудники</h1>
                        <p className="text-muted-foreground text-sm">
                            {summary.people} {plural(summary.people, ['человек', 'человека', 'человек'])} в {summary.departments}{' '}
                            {plural(summary.departments, ['отделе', 'отделах', 'отделах'])}
                        </p>
                    </div>
                    <Button className="h-9">
                        <Plus />
                        Добавить сотрудника
                    </Button>
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    <nav aria-label="Статус сотрудников" className="bg-muted flex flex-wrap gap-1 rounded-lg p-[3px]">
                        {tabs.map((tab) => {
                            const active = filters.status === tab.key;

                            return (
                                <Link
                                    key={tab.label}
                                    href={route('employees.index', query({ ...filters, status: tab.key }))}
                                    preserveState
                                    preserveScroll
                                    aria-current={active ? 'page' : undefined}
                                    className={cn(
                                        'flex h-[30px] items-center gap-2 rounded-md px-3 text-sm font-medium transition-colors',
                                        active ? 'bg-background font-semibold shadow-sm' : 'text-foreground/80 hover:text-foreground',
                                    )}
                                >
                                    {tab.label}
                                    <span className="text-muted-foreground text-xs font-semibold tabular-nums">{counts[tab.key ?? 'all']}</span>
                                </Link>
                            );
                        })}
                    </nav>

                    <div className="flex-1" />

                    <FilterMenu
                        label="Отдел"
                        value={filters.department}
                        options={options.departments}
                        onChange={(department) => apply({ department })}
                    />
                    <FilterMenu label="Должность" value={filters.position} options={options.positions} onChange={(position) => apply({ position })} />
                    <FilterMenu label="Локация" value={filters.location} options={options.locations} onChange={(location) => apply({ location })} />
                    <Button variant="outline" className="h-9 font-semibold">
                        <Download />
                        Экспорт
                    </Button>
                </div>

                <Card className="flex flex-col gap-0 overflow-hidden rounded-xl p-0 md:min-h-0 md:flex-1">
                    <div className="overflow-auto md:min-h-0 md:flex-1">
                        <table className="w-full min-w-[960px] border-collapse text-sm">
                            <thead className="bg-sidebar sticky top-0 z-10 shadow-[0_1px_0_var(--border)]">
                                <tr className="text-muted-foreground text-left text-[13px]">
                                    <th scope="col" className="px-6 py-3.5 font-semibold">
                                        Сотрудник
                                    </th>
                                    <th scope="col" className="px-4 py-3.5 font-semibold">
                                        Должность
                                    </th>
                                    <th scope="col" className="px-4 py-3.5 font-semibold">
                                        Отдел
                                    </th>
                                    <th scope="col" className="px-4 py-3.5 font-semibold">
                                        Статус
                                    </th>
                                    <th scope="col" className="px-4 py-3.5 font-semibold">
                                        Дата приёма
                                    </th>
                                    <th scope="col" className="px-4 py-3.5 font-semibold">
                                        Руководитель
                                    </th>
                                    <th scope="col" className="w-9 py-3.5 pr-6 pl-4">
                                        <span className="sr-only">Действия</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {employees.data.map((employee) => (
                                    <tr key={employee.id} className="border-t">
                                        <td className="px-6 py-3">
                                            <div className="flex items-center gap-3">
                                                <PersonAvatar name={employee.name} className="size-[38px] text-[13px]" />
                                                <div className="flex flex-col gap-0.5">
                                                    <span className="font-semibold">{employee.name}</span>
                                                    <span className="text-muted-foreground text-[13px]">{employee.email}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">{employee.position}</td>
                                        <td className="text-muted-foreground px-4 py-3">{employee.department}</td>
                                        <td className="px-4 py-3">
                                            <StatusBadge tone={statuses[employee.status].tone}>{statuses[employee.status].label}</StatusBadge>
                                        </td>
                                        <td className="text-muted-foreground px-4 py-3 tabular-nums">
                                            {format(parseISO(employee.hired_at), 'dd.MM.yyyy')}
                                        </td>
                                        <td className="px-4 py-3">{employee.manager ?? <span className="text-muted-foreground">—</span>}</td>
                                        <td className="py-3 pr-6 pl-4">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="text-muted-foreground size-9"
                                                aria-label={`Действия: ${employee.name}`}
                                            >
                                                <Ellipsis className="size-5!" />
                                            </Button>
                                        </td>
                                    </tr>
                                ))}

                                {employees.data.length === 0 && (
                                    <tr className="border-t">
                                        <td colSpan={7} className="text-muted-foreground px-6 py-16 text-center">
                                            Никого не нашлось. Попробуйте изменить фильтры.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    <Pagination paginator={employees} className="shrink-0 border-t px-6 py-3.5" />
                </Card>
            </div>
        </AppLayout>
    );
}
