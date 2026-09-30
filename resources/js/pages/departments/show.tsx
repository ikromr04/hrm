import { OrgChart, type OrgDepartment } from '@/components/org-chart';
import { PersonFace } from '@/components/person-face';
import { PersonLink } from '@/components/person-link';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import AppLayout from '@/layouts/app-layout';
import { useCan } from '@/lib/access';
import { peopleLabel } from '@/lib/employee';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ChevronRight, LayoutGrid, List, Network, Users } from 'lucide-react';
import { type ReactNode, useState } from 'react';

interface Person {
    id: number;
    /** "Фамилия Имя" */
    name: string;
    avatar: string | null;
}

interface Colleague extends Person {
    email: string;
    positions: string[];
}

interface Department {
    id: number;
    name: string;
    /** From the top of the tree down to the direct parent. */
    parents: { id: number; name: string }[];
    /** Working people here and in sub-departments, heads included. */
    total_count: number;
    heads: Colleague[];
    children: { id: number; name: string; total_count: number; heads: Person[] }[];
    /** Working members who do not lead it. */
    members: Colleague[];
}

type View = 'chart' | 'list';

/** Bumped with the default below, or a saved 'chart' would still win. */
const VIEW_KEY = 'department.view.v2';

/**
 * The list opens the page: it is what a department is usually opened for — who
 * works here — and the chart is a step aside from that.
 */
function savedView(): View {
    try {
        return localStorage.getItem(VIEW_KEY) === 'chart' ? 'chart' : 'list';
    } catch {
        return 'list';
    }
}

function Section({ title, count, children, className }: { title: string; count?: number; children: ReactNode; className?: string }) {
    return (
        <Card className={cn('flex flex-col gap-3 rounded-xl px-4 py-4 md:px-6 md:py-5', className)}>
            <h2 className="flex items-baseline gap-2 text-base font-semibold">
                {title}
                {count !== undefined && <span className="text-muted-foreground text-sm font-normal tabular-nums">{count}</span>}
            </h2>
            {children}
        </Card>
    );
}

function Avatar({ person, className }: { person: Person; className?: string }) {
    return <PersonFace id={person.id} name={person.name} avatar={person.avatar} className={className} />;
}

/** One colleague: avatar, name linking to the profile, positions and email; `compact` fits a narrow column. */
function ColleagueRow({ person, compact }: { person: Colleague; compact?: boolean }) {
    const email = (
        <a
            href={`mailto:${person.email}`}
            className={cn(
                'text-brand-strong truncate hover:underline dark:text-[#C5E27A]',
                compact ? 'self-start text-[13px]' : 'hidden shrink-0 text-sm sm:block',
            )}
        >
            {person.email}
        </a>
    );

    return (
        <li className="flex items-center gap-3 border-t py-3 first:border-t-0 first:pt-0 last:pb-0">
            <Avatar person={person} />
            <div className="flex min-w-0 flex-1 flex-col gap-1">
                <PersonLink id={person.id} className="self-start text-sm font-medium hover:underline">
                    {person.name}
                </PersonLink>
                {compact && email}
                {person.positions.length > 0 && (
                    <div className="flex flex-wrap gap-1">
                        {person.positions.map((position) => (
                            <StatusBadge key={position} tone="success" className="h-auto min-h-[22px] whitespace-normal">
                                {position}
                            </StatusBadge>
                        ))}
                    </div>
                )}
            </div>
            {!compact && email}
        </li>
    );
}

export default function DepartmentPage({ department, chart }: { department: Department; chart: OrgDepartment[] }) {
    const can = useCan();
    const [view, setView] = useState<View>(savedView);

    const changeView = (next: string) => {
        if (next !== 'chart' && next !== 'list') return;
        setView(next);
        try {
            localStorage.setItem(VIEW_KEY, next);
        } catch {
            // Blocked storage: the choice just is not remembered.
        }
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Структура компании', href: '/departments' },
        { title: department.name, href: `/departments/${department.id}` },
    ];

    return (
        // The chart scrolls inside its own frame; the list scrolls with the page.
        <AppLayout breadcrumbs={breadcrumbs} fitViewport={view === 'chart'}>
            <Head title={department.name} />

            <div className="flex flex-1 flex-col gap-4 p-3 md:min-h-0 md:px-5 md:py-4">
                <Card className="flex flex-col gap-4 rounded-xl p-4 sm:flex-row sm:items-center md:p-6">
                    <div className="flex min-w-0 flex-1 flex-col gap-1.5">
                        {department.parents.length > 0 && (
                            <nav aria-label="Входит в" className="text-muted-foreground flex flex-wrap items-center gap-1 text-sm">
                                {department.parents.map((parent) => (
                                    <span key={parent.id} className="flex items-center gap-1">
                                        <Link href={route('departments.show', parent.id)} className="hover:text-foreground hover:underline">
                                            {parent.name}
                                        </Link>
                                        <ChevronRight className="size-3.5" />
                                    </span>
                                ))}
                            </nav>
                        )}
                        <h1 className="text-xl font-semibold tracking-tight break-words">{department.name}</h1>
                        <p className="text-muted-foreground flex items-center gap-1.5 text-sm">
                            <Users className="size-4" />
                            {peopleLabel(department.total_count)}
                            {department.children.length > 0 && ' вместе с подотделами'}
                        </p>
                    </div>

                    {/* On a phone the two controls take the whole width of the card, one
                        under the other: side by side they are wider than 320px, and the
                        long label of the link would run out of its button. */}
                    <div className="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center sm:self-center">
                        {/* The structure of the company is open to everybody; the list of
                        the staff is not, and narrowing it by department takes reading
                        that line of a card. A link into a refusal is worse than none. */}
                        {department.total_count > 0 && can('employees.view') && can('employees.field.departments') && (
                            <Button variant="outline" className="h-auto min-h-10 whitespace-normal sm:h-10 sm:whitespace-nowrap" asChild>
                                <Link href={route('employees.index', { department: [department.id] })}>
                                    <List />
                                    Открыть в списке сотрудников
                                </Link>
                            </Button>
                        )}
                        <ToggleGroup
                            type="single"
                            variant="outline"
                            value={view}
                            onValueChange={changeView}
                            aria-label="Вид"
                            className="justify-start"
                        >
                            <ToggleGroupItem value="chart" className="flex-1 gap-1.5 px-3 sm:flex-none">
                                <Network />
                                Схема
                            </ToggleGroupItem>
                            <ToggleGroupItem value="list" className="flex-1 gap-1.5 px-3 sm:flex-none">
                                <LayoutGrid />
                                Список
                            </ToggleGroupItem>
                        </ToggleGroup>
                    </div>
                </Card>

                {view === 'chart' ? (
                    <Card className="flex flex-col gap-0 overflow-hidden rounded-xl p-0 md:min-h-0 md:flex-1">
                        <OrgChart departments={chart} rootId={department.id} />
                    </Card>
                ) : (
                    <div className="grid grid-cols-1 items-start gap-4 lg:grid-cols-3">
                        <div className="flex flex-col gap-4">
                            <Section title={department.heads.length > 1 ? 'Руководители' : 'Руководитель'}>
                                {department.heads.length === 0 ? (
                                    <p className="text-muted-foreground text-sm">Не назначен</p>
                                ) : (
                                    <ul className="flex flex-col">
                                        {department.heads.map((head) => (
                                            <ColleagueRow key={head.id} person={head} compact />
                                        ))}
                                    </ul>
                                )}
                            </Section>

                            {department.children.length > 0 && (
                                <Section title="Подотделы" count={department.children.length}>
                                    <ul className="-mx-2 flex flex-col">
                                        {department.children.map((child) => (
                                            <li key={child.id}>
                                                <Link
                                                    href={route('departments.show', child.id)}
                                                    className="hover:bg-muted/60 flex items-start gap-3 rounded-md px-2 py-2"
                                                >
                                                    <span className="flex min-w-0 flex-1 flex-col gap-0.5">
                                                        <span className="text-sm font-medium">{child.name}</span>
                                                        {child.heads.length > 0 && (
                                                            <span className="text-muted-foreground truncate text-xs">
                                                                {child.heads.map((head) => head.name).join(', ')}
                                                            </span>
                                                        )}
                                                    </span>
                                                    <span
                                                        className="text-muted-foreground flex shrink-0 items-center gap-1 pt-0.5 text-[13px] tabular-nums"
                                                        title={peopleLabel(child.total_count)}
                                                    >
                                                        <Users className="size-3.5" />
                                                        {child.total_count}
                                                    </span>
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                </Section>
                            )}
                        </div>

                        <Section title="Сотрудники" count={department.members.length} className="lg:col-span-2">
                            {department.members.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    {department.children.length > 0 ? 'Все сотрудники работают в подотделах.' : 'В отделе пока нет сотрудников.'}
                                </p>
                            ) : (
                                <ul className="flex flex-col">
                                    {department.members.map((member) => (
                                        <ColleagueRow key={member.id} person={member} />
                                    ))}
                                </ul>
                            )}
                        </Section>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
