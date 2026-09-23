import {
    clearedFilter,
    countActiveFilters,
    DataTable,
    resetView,
    useTableView,
    type ColumnDef as TableColumn,
    type FilterDef as TableFilter,
} from '@/components/data-table';
import { EmployeeActions, type EmploymentStatus } from '@/components/employee-actions';
import { LanguageBadges } from '@/components/language-badges';
import { Pagination, type Paginated } from '@/components/pagination';
import { PersonAvatar } from '@/components/person-avatar';
import { Phones } from '@/components/phones';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import { capitalize, formatDate, maritalLabels, sexLabels, type Marital, type PrivateDetails, type Sex, type SpokenLanguage } from '@/lib/employee';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ChevronDown, Columns3, Crown, Lock, Plus, RotateCcw, Search, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState, type ReactNode } from 'react';

/* ------------------------------------------------------------------ types */

interface EmployeeRow {
    id: number;
    name: string;
    surname: string;
    patronymic: string | null;
    avatar: string | null;
    sex: Sex;
    email: string;
    /** Access roles, shown as "Позиция". */
    roles: string[];
    /** Positions, shown as "Должность"; an employee can hold several. */
    positions: string[];
    departments: { id: number; name: string; path: string; is_head: boolean }[];
    /** Public, like positions; the best known first. */
    languages: SpokenLanguage[];
    status: EmploymentStatus;
    status_changed_at: string | null;
    /** Where they were transferred or why they were let go; managers only. */
    status_note: string | null;
    /** Null when the viewer may not see this person's private data. */
    private: PrivateDetails | null;
}

interface Filters {
    /** Toolbar search across every column. */
    q: string;
    /** "Сотрудник" column filter: name and e-mail only. */
    search: string;
    role: string[];
    position: number[];
    department: number[];
    language: number[];
    sex: Sex | null;
    birth_from: string | null;
    birth_to: string | null;
    nationality: string[];
    citizenship: string[];
    address: string;
    phone: string;
    marital_status: Marital | null;
    children: number[];
    hired_from: string | null;
    hired_to: string | null;
}

type ColumnKey =
    | 'name'
    | 'role'
    | 'position'
    | 'department'
    | 'languages'
    | 'birth_date'
    | 'sex'
    | 'nationality'
    | 'citizenship'
    | 'home_address'
    | 'phone'
    | 'marital_status'
    | 'children'
    | 'hired_at';

interface Sort {
    key: ColumnKey;
    direction: 'asc' | 'desc';
}

interface EmployeesProps {
    employees: Paginated<EmployeeRow>;
    filters: Filters;
    sort: Sort;
    perPage: number;
    perPageOptions: number[];
    /** May see, sort and filter everyone's private data (admins). */
    privateAccess: boolean;
    sortable: ColumnKey[];
    options: {
        roles: { name: string; title: string }[];
        positions: { id: number; name: string }[];
        /** The department tree flattened, parents first. */
        departments: { id: number; name: string; depth: number }[];
        languages: { id: number; name: string }[];
        nationalities: string[];
        citizenships: string[];
    };
    /** Which list is shown: working, transferred or fired. */
    status: EmploymentStatus;
    /** Per-list counts; null for viewers who only see working staff. */
    statusCounts: Record<EmploymentStatus, number> | null;
    total: number;
}

/** `depth` indents tree options such as departments. */
type Option<T> = { value: T; label: string; depth?: number };

type FilterDef =
    | { type: 'text'; param: 'search' | 'address' | 'phone'; placeholder: string }
    | { type: 'select'; param: 'sex' | 'marital_status'; options: Option<string>[] }
    | {
          type: 'multi';
          param: 'role' | 'position' | 'department' | 'language' | 'nationality' | 'citizenship' | 'children';
          options: Option<string | number>[];
      }
    | { type: 'dates'; from: 'birth_from' | 'hired_from'; to: 'birth_to' | 'hired_to' };

interface ColumnDef {
    key: ColumnKey;
    label: string;
    width: number;
    private: boolean;
    filter?: FilterDef;
    cell: (row: EmployeeRow, details: PrivateDetails) => ReactNode;
}

/* ---------------------------------------------------------------- helpers */

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Сотрудники', href: '/employees' }];

const fullName = (row: EmployeeRow) => [row.surname, row.name].filter(Boolean).join(' ');

function RoleBadges({ titles }: { titles: string[] }) {
    return (
        <div className="flex flex-wrap gap-1 whitespace-normal">
            {titles.map((title) => (
                <StatusBadge key={title} tone="info">
                    {title}
                </StatusBadge>
            ))}
        </div>
    );
}

function PositionBadges({ titles }: { titles: string[] }) {
    return (
        <div className="flex flex-wrap gap-1 whitespace-normal">
            {titles.map((title) => (
                <StatusBadge key={title} tone="success">
                    {title}
                </StatusBadge>
            ))}
        </div>
    );
}

function DepartmentBadges({ departments }: { departments: EmployeeRow['departments'] }) {
    return (
        <div className="flex flex-wrap gap-1 whitespace-normal">
            {departments.map((department) => (
                <Link
                    key={department.id}
                    href={route('departments.show', department.id)}
                    className="group focus-visible:ring-ring rounded-md outline-hidden focus-visible:ring-2"
                >
                    <StatusBadge
                        tone="neutral"
                        title={department.is_head ? `${department.path} · руководитель` : department.path}
                        className="h-auto min-h-[22px] gap-1 py-0.5 whitespace-normal transition-colors group-hover:bg-[#E4E4E7] group-hover:text-[#18181B] dark:group-hover:bg-white/20 dark:group-hover:text-white"
                    >
                        {department.is_head && <Crown className="size-3 shrink-0 text-[#9A4A06] dark:text-[#F8C471]" aria-label="Руководитель" />}
                        {department.name}
                    </StatusBadge>
                </Link>
            ))}
        </div>
    );
}

const statusTabs: { status: EmploymentStatus; label: string }[] = [
    { status: 'active', label: 'Работают' },
    { status: 'transferred', label: 'Переведённые' },
    { status: 'fired', label: 'Уволенные' },
];

/** "Уволена 12.03.2026 · По собственному желанию" under the name. */
function LeftBadge({ row }: { row: EmployeeRow }) {
    const female = row.sex === 'female';
    const label = row.status === 'fired' ? (female ? 'Уволена' : 'Уволен') : female ? 'Переведена' : 'Переведён';
    const text = [label, formatDate(row.status_changed_at)].filter(Boolean).join(' ');

    return (
        <span className="mt-1 flex min-w-0 flex-col items-start gap-1 text-xs whitespace-normal">
            <StatusBadge tone={row.status === 'fired' ? 'danger' : 'warning'} className="h-5 shrink-0">
                {text}
            </StatusBadge>
            {row.status_note && (
                <span className="text-muted-foreground leading-snug break-words">
                    {row.status === 'transferred' ? `→ ${row.status_note}` : row.status_note}
                </span>
            )}
        </span>
    );
}

function Empty() {
    return <span className="text-muted-foreground">—</span>;
}

function Children({ items }: { items: PrivateDetails['children'] }) {
    if (items.length === 0) return <span className="text-muted-foreground">Нет</span>;

    const names = items.map((child) => [child.full_name, formatDate(child.birth_date)].filter(Boolean).join(' — ')).join('\n');

    return (
        <span title={names} className="cursor-help underline decoration-dotted underline-offset-4">
            {items.length}
        </span>
    );
}

/* ---------------------------------------------------------------- columns */

function buildColumns(options: EmployeesProps['options']): ColumnDef[] {
    return [
        {
            key: 'name',
            label: 'Сотрудник',
            width: 320,
            private: false,
            filter: { type: 'text', param: 'search', placeholder: 'ФИО или почта' },
            cell: (row) => (
                <div className="flex items-center gap-3">
                    {row.avatar ? (
                        <img src={row.avatar} alt="" className="size-[38px] shrink-0 rounded-full object-cover" />
                    ) : (
                        <PersonAvatar name={`${row.name} ${row.surname}`} className="size-[38px] text-[13px]" />
                    )}
                    <div className="flex min-w-0 flex-col gap-0.5">
                        <Link
                            href={route('employees.show', row.id)}
                            className="hover:text-brand-strong truncate font-semibold hover:underline dark:hover:text-[#C5E27A]"
                        >
                            {fullName(row)}
                        </Link>
                        <a
                            href={`mailto:${row.email}`}
                            className="text-brand-strong truncate text-[13px] hover:underline dark:text-[#C5E27A]"
                            title={`Написать: ${row.email}`}
                        >
                            {row.email}
                        </a>
                        {row.status !== 'active' && <LeftBadge row={row} />}
                    </div>
                </div>
            ),
        },
        {
            key: 'role',
            label: 'Позиция',
            width: 240,
            private: false,
            filter: { type: 'multi', param: 'role', options: options.roles.map((r) => ({ value: r.name, label: r.title })) },
            cell: (row) => (row.roles.length ? <RoleBadges titles={row.roles} /> : <Empty />),
        },
        {
            key: 'department',
            label: 'Отдел / Департамент',
            width: 300,
            private: false,
            filter: {
                type: 'multi',
                param: 'department',
                options: options.departments.map((d) => ({ value: d.id, label: d.name, depth: d.depth })),
            },
            cell: (row) => (row.departments.length ? <DepartmentBadges departments={row.departments} /> : <Empty />),
        },
        {
            key: 'position',
            label: 'Должность',
            width: 240,
            private: false,
            filter: { type: 'multi', param: 'position', options: options.positions.map((t) => ({ value: t.id, label: t.name })) },
            cell: (row) => (row.positions.length ? <PositionBadges titles={row.positions} /> : <Empty />),
        },
        {
            key: 'languages',
            label: 'Языки',
            width: 260,
            private: false,
            filter: { type: 'multi', param: 'language', options: options.languages.map((l) => ({ value: l.id, label: l.name })) },
            cell: (row) => (row.languages.length ? <LanguageBadges languages={row.languages} /> : <Empty />),
        },
        {
            key: 'birth_date',
            label: 'Дата рождения',
            width: 200,
            private: true,
            filter: { type: 'dates', from: 'birth_from', to: 'birth_to' },
            cell: (_, d) => <span className="tabular-nums">{formatDate(d.birth_date) ?? <Empty />}</span>,
        },
        {
            key: 'sex',
            label: 'Пол',
            width: 130,
            private: false,
            filter: {
                type: 'select',
                param: 'sex',
                options: [
                    { value: 'male', label: 'Мужской' },
                    { value: 'female', label: 'Женский' },
                ],
            },
            cell: (row) => sexLabels[row.sex],
        },
        {
            key: 'nationality',
            label: 'Национальность',
            width: 210,
            private: true,
            filter: { type: 'multi', param: 'nationality', options: options.nationalities.map((n) => ({ value: n, label: capitalize(n) })) },
            cell: (_, d) => (d.nationality ? capitalize(d.nationality) : <Empty />),
        },
        {
            key: 'citizenship',
            label: 'Гражданство',
            width: 190,
            private: true,
            filter: { type: 'multi', param: 'citizenship', options: options.citizenships.map((c) => ({ value: c, label: c })) },
            cell: (_, d) => d.citizenship ?? <Empty />,
        },
        {
            key: 'home_address',
            label: 'Домашний адрес',
            width: 280,
            private: true,
            filter: { type: 'text', param: 'address', placeholder: 'Улица, дом…' },
            cell: (_, d) => <span className="whitespace-normal">{d.home_address ?? <Empty />}</span>,
        },
        {
            key: 'phone',
            label: 'Телефон',
            width: 200,
            private: true,
            filter: { type: 'text', param: 'phone', placeholder: 'Цифры номера' },
            cell: (_, d) => <Phones phone={d.phone} sos={d.sos_phone} sosContact={d.sos_contact} />,
        },
        {
            key: 'marital_status',
            label: 'Семейное положение',
            width: 240,
            private: true,
            filter: {
                type: 'select',
                param: 'marital_status',
                options: [
                    { value: 'married', label: 'В браке' },
                    { value: 'single', label: 'Не в браке' },
                ],
            },
            cell: (row, d) => (d.marital_status ? maritalLabels[row.sex][d.marital_status] : <Empty />),
        },
        {
            key: 'children',
            label: 'Дети',
            width: 130,
            private: true,
            filter: {
                type: 'multi',
                param: 'children',
                options: [
                    { value: 0, label: 'Нет детей' },
                    { value: 1, label: '1' },
                    { value: 2, label: '2' },
                    { value: 3, label: '3 и более' },
                ],
            },
            cell: (_, d) => <Children items={d.children} />,
        },
        {
            key: 'hired_at',
            label: 'Начало работы',
            width: 200,
            private: true,
            filter: { type: 'dates', from: 'hired_from', to: 'hired_to' },
            cell: (_, d) => <span className="tabular-nums">{formatDate(d.hired_at) ?? <Empty />}</span>,
        },
    ];
}

/* ------------------------------------------------------ view preferences */

const STORAGE_KEY = 'employees.table.view.v4';

function defaultView(columns: ColumnDef[], privateAccess: boolean) {
    return {
        // Without private access those columns are locks for everyone but yourself.
        hidden: privateAccess ? [] : columns.filter((c) => c.private).map((c) => c.key as string),
        pinned: { left: ['name'], right: [] as string[] },
    };
}

/* ------------------------------------------------------------ URL params */

const DEFAULT_SORT: Sort = { key: 'name', direction: 'asc' };

type QueryValue = string | number | (string | number)[] | null;

function toParams(
    filters: Filters,
    sort: Sort,
    perPage: number,
    defaultPerPage: number,
    status: EmploymentStatus,
): Record<string, Exclude<QueryValue, null>> {
    const params: Record<string, QueryValue> = {
        ...filters,
        status: status === 'active' ? null : status,
        sort: sort.key === DEFAULT_SORT.key ? null : sort.key,
        direction: sort.direction === DEFAULT_SORT.direction ? null : sort.direction,
        per_page: perPage === defaultPerPage ? null : perPage,
    };

    return Object.fromEntries(
        Object.entries(params).filter(([, value]) => (Array.isArray(value) ? value.length > 0 : value !== null && value !== '')),
    ) as Record<string, Exclude<QueryValue, null>>;
}

/* ---------------------------------------------------------------- page */

export default function Employees({
    employees,
    filters,
    sort,
    perPage,
    perPageOptions,
    privateAccess,
    sortable,
    options,
    status,
    statusCounts,
}: EmployeesProps) {
    const { auth } = usePage<SharedData>().props;
    const canManage = auth.can.manageEmployees;
    const columns = useMemo(() => buildColumns(options), [options]);
    const defaults = useMemo(() => defaultView(columns, privateAccess), [columns, privateAccess]);
    const { view, setView, pin, toggleHidden } = useTableView(
        STORAGE_KEY,
        columns.map((c) => c.key),
        defaults,
    );
    const [search, setSearch] = useState(filters.q);
    const firstRender = useRef(true);

    const visit = (next: { filters?: Partial<Filters>; sort?: Sort; perPage?: number; status?: EmploymentStatus }) => {
        router.get(
            route('employees.index'),
            toParams({ ...filters, ...next.filters }, next.sort ?? sort, next.perPage ?? perPage, perPageOptions[0], next.status ?? status),
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };
    const applyFilters = (changes: Partial<Filters>) => visit({ filters: changes });

    useEffect(() => setSearch(filters.q), [filters.q]);
    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }
        if (search === filters.q) return;

        const timer = setTimeout(() => applyFilters({ q: search }), 300);
        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const isHidden = (key: string) => view.hidden.includes(key);

    // A column may carry a filter the viewer must not use on everyone.
    const canFilter = (column: ColumnDef) => Boolean(column.filter) && (!column.private || privateAccess);
    const activeFilters = countActiveFilters(columns as unknown as TableColumn[], filters as unknown as Record<string, unknown>, (column) =>
        canFilter(column as unknown as ColumnDef),
    );

    const sortBy = (key: string, direction?: 'asc' | 'desc') =>
        visit({ sort: { key: key as ColumnKey, direction: direction ?? (sort.key === key && sort.direction === 'asc' ? 'desc' : 'asc') } });

    return (
        <AppLayout breadcrumbs={breadcrumbs} fitViewport>
            <Head title="Сотрудники" />

            <div className="flex flex-1 flex-col gap-4 p-3 md:min-h-0 md:px-5 md:py-4">
                <h1 className="text-xl font-semibold tracking-tight">Сотрудники</h1>

                <div className="-mb-2 flex flex-wrap items-center gap-2">
                    <label className="border-input bg-background text-muted-foreground focus-within:ring-ring flex h-8 min-w-48 flex-1 items-center gap-2 rounded-md border px-3 shadow-xs focus-within:ring-2">
                        <Search className="size-4 shrink-0" />
                        <span className="sr-only">Поиск по всем полям</span>
                        <input
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Поиск по всем полям"
                            className="text-foreground min-w-0 flex-1 bg-transparent text-sm outline-hidden"
                        />
                    </label>

                    {statusCounts && (
                        <nav aria-label="Списки сотрудников" className="flex items-center gap-1 text-sm">
                            {statusTabs.map((tab) => (
                                <button
                                    key={tab.status}
                                    type="button"
                                    onClick={() => visit({ status: tab.status })}
                                    aria-current={status === tab.status ? 'page' : undefined}
                                    className={cn(
                                        'flex h-8 items-center gap-1.5 rounded-md px-2.5 transition-colors',
                                        status === tab.status
                                            ? 'bg-brand-soft text-foreground font-semibold dark:bg-white/10'
                                            : 'text-muted-foreground hover:bg-accent hover:text-foreground',
                                    )}
                                >
                                    {tab.label}
                                    <span className="text-muted-foreground text-xs font-semibold tabular-nums">{statusCounts[tab.status]}</span>
                                </button>
                            ))}
                        </nav>
                    )}

                    {activeFilters > 0 && (
                        <Button
                            variant="ghost"
                            className="h-8"
                            onClick={() =>
                                applyFilters(
                                    columns.filter(canFilter).reduce<Partial<Filters>>((acc, c) => ({ ...acc, ...clearedFilter(c.filter!) }), {}),
                                )
                            }
                        >
                            <X />
                            Сбросить фильтры ({activeFilters})
                        </Button>
                    )}

                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="outline" className="h-8 font-normal">
                                <Columns3 />
                                Колонки
                                <ChevronDown className="text-muted-foreground" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="max-h-96 w-60 overflow-y-auto">
                            <DropdownMenuLabel>Показывать колонки</DropdownMenuLabel>
                            {columns.map((column) => (
                                <DropdownMenuCheckboxItem
                                    key={column.key}
                                    checked={!isHidden(column.key)}
                                    disabled={column.key === 'name'}
                                    onCheckedChange={(checked) => toggleHidden(column.key, !checked)}
                                    onSelect={(event) => event.preventDefault()}
                                >
                                    {column.label}
                                </DropdownMenuCheckboxItem>
                            ))}
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                onSelect={() => {
                                    resetView(STORAGE_KEY);
                                    setView(defaults);
                                }}
                            >
                                <RotateCcw />
                                Сбросить вид
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <Button className="h-8">
                        <Plus />
                        Добавить сотрудника
                    </Button>
                </div>

                <DataTable
                    columns={columns as unknown as TableColumn[]}
                    rows={employees.data}
                    rowKey={(row) => row.id}
                    renderCell={(column, row) => {
                        const own = column as unknown as ColumnDef;

                        // A private column is a lock for anyone who may not see it.
                        return own.private && !row.private ? (
                            <Lock className="text-muted-foreground/60 size-4" aria-label="Закрытые данные" />
                        ) : (
                            own.cell(row, row.private as PrivateDetails)
                        );
                    }}
                    sort={sort}
                    sortable={sortable}
                    onSort={sortBy}
                    filters={filters as unknown as Record<string, unknown>}
                    onFilter={(changes) => applyFilters(changes as Partial<Filters>)}
                    canFilter={(column) => canFilter(column as unknown as ColumnDef)}
                    wideFilter={(column) => {
                        // The department tree needs more room than a short list.
                        const filter = column.filter as TableFilter | undefined;

                        return filter?.type === 'multi' && filter.param === 'department';
                    }}
                    view={view}
                    onPin={pin}
                    onHide={(key) => toggleHidden(key, true)}
                    lockedKey="name"
                    actions={canManage ? (row) => <EmployeeActions employee={row} isSelf={row.id === auth.user.id} /> : undefined}
                    empty={<Empty />}
                    footer={
                        <>
                            <div className="text-muted-foreground flex items-center gap-2 text-sm">
                                Строк на странице
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="outline" size="sm" className="h-8 gap-1 px-2.5 tabular-nums">
                                            {perPage}
                                            <ChevronDown className="text-muted-foreground" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="start" className="min-w-20">
                                        <DropdownMenuRadioGroup value={String(perPage)} onValueChange={(value) => visit({ perPage: Number(value) })}>
                                            {perPageOptions.map((option) => (
                                                <DropdownMenuRadioItem key={option} value={String(option)} className="tabular-nums">
                                                    {option}
                                                </DropdownMenuRadioItem>
                                            ))}
                                        </DropdownMenuRadioGroup>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                            <Pagination paginator={employees} className="min-w-0 flex-1" />
                        </>
                    }
                />
            </div>
        </AppLayout>
    );
}
