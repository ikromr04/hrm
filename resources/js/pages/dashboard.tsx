import { StatusBadge, type StatusTone } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { shortMonths } from '@/lib/employee';
import { eventLabel, eventTone, type EventKind } from '@/lib/equipment';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { format, parseISO } from 'date-fns';
import { ru } from 'date-fns/locale';
import { ChevronRight, Laptop, Plus, UserMinus, UserPlus, Users, type LucideIcon } from 'lucide-react';
import { type ReactNode } from 'react';

interface JournalRow {
    id: number;
    kind: EventKind;
    at: string | null;
    unit: { id: number; name: string; inventory_number: string | null } | null;
    actor: string | null;
}

interface DashboardProps {
    /** The first day of the recent stretch the tiles count, and today. */
    since: string;
    today: string;
    staff: { active: number; hired: number; fired: number };
    equipment: { issued: number; stock: number; service: number };
    departments: { id: number; name: string; count: number }[];
    events: JournalRow[];
}

interface Stat {
    key: string;
    label: string;
    value: number;
    href: string;
    icon: LucideIcon;
    notes?: { text: string; tone: StatusTone }[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Главная', href: '/dashboard' }];

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

function SectionLink({ href, children }: { href: string; children: ReactNode }) {
    return (
        <Link href={href} className="text-muted-foreground hover:text-foreground flex items-center gap-0.5 text-sm font-medium">
            {children}
            <ChevronRight className="size-4" />
        </Link>
    );
}

function Empty({ children }: { children: ReactNode }) {
    return <p className="text-muted-foreground text-sm">{children}</p>;
}

function StatCard({ stat }: { stat: Stat }) {
    const Icon = stat.icon;

    return (
        <Link href={stat.href} className="group rounded-xl">
            <Card className="group-hover:border-brand/60 flex h-full flex-col gap-3.5 rounded-xl p-5 transition-colors">
                <div className="text-muted-foreground flex items-center gap-2.5 text-sm font-medium">
                    <span className="bg-brand-soft text-brand-strong flex size-8 items-center justify-center rounded-lg dark:bg-white/10 dark:text-[#C5E27A]">
                        <Icon className="size-[18px]" />
                    </span>
                    {stat.label}
                </div>
                <div className="flex flex-wrap items-baseline gap-2.5">
                    <span className="text-3xl font-bold tracking-tight tabular-nums">{stat.value}</span>
                    {stat.notes?.map((note) => (
                        <StatusBadge key={note.text} tone={note.tone}>
                            {note.text}
                        </StatusBadge>
                    ))}
                </div>
            </Card>
        </Link>
    );
}

/** "Инв. № EV-0012 · Рахимов Фарход": what a journal line adds under the unit's name. */
function eventDetails(event: JournalRow): string {
    const parts = [event.unit?.inventory_number ? `Инв. № ${event.unit.inventory_number}` : null, event.actor];

    return parts.filter(Boolean).join(' · ') || '—';
}

export default function Dashboard({ since, today, staff, equipment, departments, events }: DashboardProps) {
    const heading = capitalize(format(parseISO(today), 'EEEE, d MMMM yyyy', { locale: ru }));
    const maxDepartment = Math.max(...departments.map((department) => department.count), 1);

    const equipmentNotes: Stat['notes'] = [{ text: `${equipment.stock} на балансе`, tone: 'neutral' }];
    if (equipment.service > 0) {
        equipmentNotes.push({ text: `${equipment.service} в обслуживании`, tone: 'warning' });
    }

    const stats: Stat[] = [
        { key: 'active', label: 'Работают', value: staff.active, href: '/employees', icon: Users },
        {
            key: 'hired',
            label: 'Принято за 30 дней',
            value: staff.hired,
            href: `/employees?hired_from=${since}&hired_to=${today}`,
            icon: UserPlus,
        },
        { key: 'fired', label: 'Уволено за 30 дней', value: staff.fired, href: '/employees?status=fired', icon: UserMinus },
        { key: 'equipment', label: 'Техника на руках', value: equipment.issued, href: '/equipment?tab=issued', icon: Laptop, notes: equipmentNotes },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Главная" />

            <div className="flex flex-1 flex-col gap-4 p-3 md:px-5 md:py-4">
                <div className="flex flex-wrap items-end gap-4">
                    <div className="flex flex-1 flex-col gap-1">
                        <h1 className="text-xl font-semibold tracking-tight">Обзор</h1>
                        <p className="text-muted-foreground text-sm">{heading}</p>
                    </div>
                    <Button className="h-8" asChild>
                        <Link href="/employees/create">
                            <Plus />
                            Добавить сотрудника
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    {stats.map((stat) => (
                        <StatCard key={stat.key} stat={stat} />
                    ))}
                </div>

                <div className="grid gap-5 lg:grid-cols-[minmax(0,1.55fr)_minmax(0,1fr)]">
                    <Section title="Численность по отделам" aside={<SectionLink href="/departments">Структура</SectionLink>}>
                        {departments.length === 0 ? (
                            <Empty>Отделов пока нет.</Empty>
                        ) : (
                            <ul className="flex flex-col gap-3.5">
                                {departments.map((department) => (
                                    <li key={department.id} className="grid grid-cols-[120px_minmax(0,1fr)_36px] items-center gap-3 text-sm">
                                        <Link
                                            href={`/departments/${department.id}`}
                                            className="text-muted-foreground hover:text-foreground truncate"
                                            title={department.name}
                                        >
                                            {department.name}
                                        </Link>
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
                        )}
                    </Section>

                    <Section title="Операции с техникой" aside={<SectionLink href="/equipment/journal">Журнал</SectionLink>}>
                        {events.length === 0 ? (
                            <Empty>Операций с техникой пока не было.</Empty>
                        ) : (
                            <ul className="flex flex-col gap-3.5">
                                {events.map((event) => {
                                    const date = event.at ? parseISO(event.at) : null;

                                    return (
                                        <li key={event.id} className="flex items-center gap-3.5">
                                            <time
                                                dateTime={event.at ?? undefined}
                                                className="bg-muted flex h-[52px] w-12 shrink-0 flex-col items-center justify-center rounded-lg"
                                            >
                                                <span className="text-lg leading-none font-bold tabular-nums">{date ? format(date, 'dd') : '—'}</span>
                                                <span className="text-muted-foreground text-xs">{date ? shortMonths[date.getMonth()] : ''}</span>
                                            </time>
                                            <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                                                {event.unit ? (
                                                    <Link
                                                        href={`/equipment/${event.unit.id}`}
                                                        className="truncate text-sm font-semibold hover:underline"
                                                    >
                                                        {event.unit.name}
                                                    </Link>
                                                ) : (
                                                    <span className="text-muted-foreground truncate text-sm font-semibold">Единица удалена</span>
                                                )}
                                                <span className="text-muted-foreground truncate text-[13px]">{eventDetails(event)}</span>
                                            </div>
                                            <StatusBadge tone={eventTone[event.kind]}>{eventLabel[event.kind]}</StatusBadge>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                    </Section>
                </div>
            </div>
        </AppLayout>
    );
}
