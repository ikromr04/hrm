import { PersonAvatar } from '@/components/person-avatar';
import { StatusBadge, type StatusTone } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { format, parseISO } from 'date-fns';
import { ru } from 'date-fns/locale';
import { Bell, Briefcase, CalendarDays, Check, LineChart, Plus, Users, X, type LucideIcon } from 'lucide-react';
import { type ReactNode } from 'react';

interface Stat {
    key: string;
    label: string;
    value: string;
    note: string;
    tone: StatusTone;
}

interface PersonRow {
    id: number;
    name: string;
    details: string;
}

interface AbsentRow extends PersonRow {
    status: string;
    tone: StatusTone;
}

interface DashboardProps {
    stats: Stat[];
    pendingRequests: { total: number; items: PersonRow[] };
    absentToday: AbsentRow[];
    probationAlert: { title: string; description: string } | null;
    departments: { name: string; count: number }[];
    events: { date: string; title: string; details: string }[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Главная', href: '/dashboard' }];

const statIcons: Record<string, LucideIcon> = {
    employees: Users,
    vacancies: Briefcase,
    absent: CalendarDays,
    turnover: LineChart,
};

const SHORT_MONTHS = ['янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];

function capitalize(text: string) {
    return text.charAt(0).toUpperCase() + text.slice(1);
}

function Section({ title, aside, children, className }: { title: string; aside?: ReactNode; children: ReactNode; className?: string }) {
    return (
        <Card className={cn('flex flex-col gap-4 rounded-xl px-6 py-5', className)}>
            <div className="flex items-center gap-3">
                <h2 className="flex-1 text-base font-semibold">{title}</h2>
                {aside}
            </div>
            {children}
        </Card>
    );
}

function StatCard({ stat }: { stat: Stat }) {
    const Icon = statIcons[stat.key] ?? Users;

    return (
        <Card className="flex flex-col gap-3.5 rounded-xl p-5">
            <div className="text-muted-foreground flex items-center gap-2.5 text-sm font-medium">
                <span className="bg-brand-soft text-brand-strong flex size-8 items-center justify-center rounded-lg dark:bg-white/10 dark:text-[#C5E27A]">
                    <Icon className="size-[18px]" />
                </span>
                {stat.label}
            </div>
            <div className="flex flex-wrap items-baseline gap-2.5">
                <span className="text-3xl font-bold tracking-tight tabular-nums">{stat.value}</span>
                <StatusBadge tone={stat.tone}>{stat.note}</StatusBadge>
            </div>
        </Card>
    );
}

export default function Dashboard({ stats, pendingRequests, absentToday, probationAlert, departments, events }: DashboardProps) {
    const today = capitalize(format(new Date(), 'EEEE, d MMMM yyyy', { locale: ru }));
    const maxDepartment = Math.max(...departments.map((department) => department.count), 1);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Главная" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:px-8 md:py-6">
                <div className="flex flex-wrap items-end gap-4">
                    <div className="flex flex-1 flex-col gap-1">
                        <h1 className="text-2xl font-semibold tracking-tight">Обзор</h1>
                        <p className="text-muted-foreground text-sm">{today}</p>
                    </div>
                    <Button className="h-9">
                        <Plus />
                        Добавить сотрудника
                    </Button>
                </div>

                <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    {stats.map((stat) => (
                        <StatCard key={stat.key} stat={stat} />
                    ))}
                </div>

                <div className="grid gap-5 lg:grid-cols-[minmax(0,1.55fr)_minmax(0,1fr)]">
                    <Section
                        title="Заявки на согласование"
                        aside={<StatusBadge tone="warning">{pendingRequests.total} ожидают</StatusBadge>}
                        className="pb-2"
                    >
                        <ul className="flex flex-col">
                            {pendingRequests.items.map((request) => (
                                <li key={request.id} className="flex items-center gap-3.5 border-t py-3.5">
                                    <PersonAvatar name={request.name} />
                                    <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                                        <span className="truncate text-sm font-semibold">{request.name}</span>
                                        <span className="text-muted-foreground truncate text-[13px]">{request.details}</span>
                                    </div>
                                    <Button
                                        variant="outline"
                                        size="icon"
                                        className="size-9 text-[#B42318] hover:text-[#B42318] dark:text-[#F7A19A]"
                                        aria-label={`Отклонить заявку: ${request.name}`}
                                    >
                                        <X />
                                    </Button>
                                    <Button className="bg-brand-soft text-brand-strong hover:bg-brand-soft/70 h-9 font-semibold dark:bg-white/10 dark:text-[#C5E27A]">
                                        <Check />
                                        <span className="sr-only sm:not-sr-only">Одобрить</span>
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    </Section>

                    <Section title="Кто отсутствует сегодня">
                        <ul className="flex flex-col gap-4">
                            {absentToday.map((person) => (
                                <li key={person.id} className="flex items-center gap-3">
                                    <PersonAvatar name={person.name} className="size-8" />
                                    <div className="flex min-w-0 flex-1 flex-col">
                                        <span className="truncate text-sm font-semibold">{person.name}</span>
                                        <span className="text-muted-foreground truncate text-[13px]">{person.details}</span>
                                    </div>
                                    <StatusBadge tone={person.tone}>{person.status}</StatusBadge>
                                </li>
                            ))}
                        </ul>

                        {probationAlert && (
                            <div className="dark:text-foreground mt-auto flex items-center gap-3 rounded-xl bg-[#FBEFD9] p-3.5 text-[#2A2D31] dark:bg-[#F5A524]/15">
                                <Bell className="size-5 shrink-0 text-[#9A4A06] dark:text-[#F8C471]" />
                                <div className="flex flex-col gap-0.5">
                                    <span className="text-sm font-semibold">{probationAlert.title}</span>
                                    <span className="text-[13px] text-[#6B4A1E] dark:text-[#F8C471]">{probationAlert.description}</span>
                                </div>
                            </div>
                        )}
                    </Section>
                </div>

                <div className="grid gap-5 lg:grid-cols-[minmax(0,1.55fr)_minmax(0,1fr)]">
                    <Section title="Численность по отделам">
                        <ul className="flex flex-col gap-3.5">
                            {departments.map((department) => (
                                <li key={department.name} className="grid grid-cols-[120px_minmax(0,1fr)_36px] items-center gap-3 text-sm">
                                    <span className="text-muted-foreground truncate">{department.name}</span>
                                    <div className="bg-muted h-2.5 rounded-full">
                                        <div
                                            className="bg-brand h-2.5 rounded-full"
                                            style={{ width: `${Math.round((department.count / maxDepartment) * 100)}%` }}
                                        />
                                    </div>
                                    <span className="text-right font-semibold tabular-nums">{department.count}</span>
                                </li>
                            ))}
                        </ul>
                    </Section>

                    <Section title="Ближайшие события">
                        <ul className="flex flex-col gap-3.5">
                            {events.map((event) => {
                                const date = parseISO(event.date);

                                return (
                                    <li key={`${event.date}-${event.title}`} className="flex items-center gap-3.5">
                                        <time
                                            dateTime={event.date}
                                            className="bg-muted flex h-[52px] w-12 shrink-0 flex-col items-center justify-center rounded-lg"
                                        >
                                            <span className="text-lg leading-none font-bold tabular-nums">{format(date, 'dd')}</span>
                                            <span className="text-muted-foreground text-xs">{SHORT_MONTHS[date.getMonth()]}</span>
                                        </time>
                                        <div className="flex min-w-0 flex-col gap-0.5">
                                            <span className="text-sm font-semibold">{event.title}</span>
                                            <span className="text-muted-foreground text-[13px]">{event.details}</span>
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                    </Section>
                </div>
            </div>
        </AppLayout>
    );
}
