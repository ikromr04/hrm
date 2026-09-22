import { Pagination, type Paginated } from '@/components/pagination';
import { PersonAvatar } from '@/components/person-avatar';
import { Phones } from '@/components/phones';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import AppLayout from '@/layouts/app-layout';
import { capitalize, formatDate, maritalLabels, sexLabels, type Marital, type PrivateDetails, type Sex } from '@/lib/employee';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    ChevronDown,
    Columns3,
    EllipsisVertical,
    EyeOff,
    ListFilter,
    Lock,
    Pin,
    PinOff,
    Plus,
    RotateCcw,
    Search,
    X,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState, type CSSProperties, type ReactNode } from 'react';

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
    departments: { id: number; name: string; path: string }[];
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
        nationalities: string[];
        citizenships: string[];
    };
    total: number;
}

/** `depth` indents tree options such as departments. */
type Option<T> = { value: T; label: string; depth?: number };

type FilterDef =
    | { type: 'text'; param: 'search' | 'address' | 'phone'; placeholder: string }
    | { type: 'select'; param: 'sex' | 'marital_status'; options: Option<string>[] }
    | { type: 'multi'; param: 'role' | 'position' | 'department' | 'nationality' | 'citizenship' | 'children'; options: Option<string | number>[] }
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
                <StatusBadge key={department.id} tone="neutral" title={department.path} className="h-auto min-h-[22px] py-0.5 whitespace-normal">
                    {department.name}
                </StatusBadge>
            ))}
        </div>
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
            cell: (_, d) => <Phones phone={d.phone} sos={d.sos_phone} />,
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

interface ViewState {
    hidden: ColumnKey[];
    pinned: { left: ColumnKey[]; right: ColumnKey[] };
}

const STORAGE_KEY = 'employees.table.view.v4';

function defaultView(columns: ColumnDef[], privateAccess: boolean): ViewState {
    return {
        // Without private access those columns are locks for everyone but yourself.
        hidden: privateAccess ? [] : columns.filter((c) => c.private).map((c) => c.key),
        pinned: { left: ['name'], right: [] },
    };
}

function loadView(fallback: ViewState, keys: ColumnKey[]): ViewState {
    try {
        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) ?? 'null') as ViewState | null;
        if (!saved?.hidden || !saved?.pinned) return fallback;

        // A saved view may mention columns that were renamed or removed since.
        const known = (list: ColumnKey[]) => list.filter((key) => keys.includes(key));
        return { hidden: known(saved.hidden), pinned: { left: known(saved.pinned.left), right: known(saved.pinned.right) } };
    } catch {
        return fallback;
    }
}

function saveView(view: ViewState | null) {
    try {
        if (view) localStorage.setItem(STORAGE_KEY, JSON.stringify(view));
        else localStorage.removeItem(STORAGE_KEY);
    } catch {
        // Storage can be unavailable (private mode); the view just won't persist.
    }
}

/* ------------------------------------------------------------ URL params */

const DEFAULT_SORT: Sort = { key: 'name', direction: 'asc' };

type QueryValue = string | number | (string | number)[] | null;

function toParams(filters: Filters, sort: Sort, perPage: number, defaultPerPage: number): Record<string, Exclude<QueryValue, null>> {
    const params: Record<string, QueryValue> = {
        ...filters,
        sort: sort.key === DEFAULT_SORT.key ? null : sort.key,
        direction: sort.direction === DEFAULT_SORT.direction ? null : sort.direction,
        per_page: perPage === defaultPerPage ? null : perPage,
    };

    return Object.fromEntries(
        Object.entries(params).filter(([, value]) => (Array.isArray(value) ? value.length > 0 : value !== null && value !== '')),
    ) as Record<string, Exclude<QueryValue, null>>;
}

function isFilterActive(filter: FilterDef, filters: Filters) {
    switch (filter.type) {
        case 'text':
            return filters[filter.param] !== '';
        case 'select':
            return filters[filter.param] !== null;
        case 'multi':
            return filters[filter.param].length > 0;
        case 'dates':
            return filters[filter.from] !== null || filters[filter.to] !== null;
    }
}

function clearedFilter(filter: FilterDef): Partial<Filters> {
    switch (filter.type) {
        case 'text':
            return { [filter.param]: '' };
        case 'select':
            return { [filter.param]: null };
        case 'multi':
            return { [filter.param]: [] };
        case 'dates':
            return { [filter.from]: null, [filter.to]: null };
    }
}

/* ---------------------------------------------------------- filter popover */

function FilterBody({ filter, filters, onApply }: { filter: FilterDef; filters: Filters; onApply: (changes: Partial<Filters>) => void }) {
    const [text, setText] = useState(filter.type === 'text' ? filters[filter.param] : '');
    const [from, setFrom] = useState(filter.type === 'dates' ? (filters[filter.from] ?? '') : '');
    const [to, setTo] = useState(filter.type === 'dates' ? (filters[filter.to] ?? '') : '');

    if (filter.type === 'text') {
        return (
            <form
                className="flex flex-col gap-2"
                onSubmit={(event) => {
                    event.preventDefault();
                    onApply({ [filter.param]: text.trim() });
                }}
            >
                <Input autoFocus value={text} onChange={(event) => setText(event.target.value)} placeholder={filter.placeholder} className="h-9" />
                <Button type="submit" size="sm">
                    Применить
                </Button>
            </form>
        );
    }

    if (filter.type === 'select') {
        const value = filters[filter.param];

        return (
            <div className="flex flex-col gap-1" role="radiogroup">
                {[{ value: null, label: 'Все' }, ...filter.options].map((option) => (
                    <button
                        key={option.label}
                        type="button"
                        role="radio"
                        aria-checked={value === option.value}
                        onClick={() => onApply({ [filter.param]: option.value })}
                        className={cn('hover:bg-accent rounded-md px-2 py-1.5 text-left text-sm', value === option.value && 'bg-accent font-medium')}
                    >
                        {option.label}
                    </button>
                ))}
            </div>
        );
    }

    if (filter.type === 'multi') {
        const selected = filters[filter.param] as (string | number)[];
        const toggle = (value: string | number, on: boolean) =>
            onApply({ [filter.param]: on ? [...selected, value] : selected.filter((item) => item !== value) });

        return (
            <div className="flex max-h-72 flex-col gap-0.5 overflow-y-auto">
                {filter.options.length === 0 && <p className="text-muted-foreground px-2 py-1.5 text-sm">Нет значений</p>}
                {filter.options.map((option) => {
                    const id = `filter-${filter.param}-${option.value}`;

                    return (
                        <div
                            key={id}
                            className="hover:bg-accent flex items-center gap-2 rounded-md px-2 py-1.5"
                            style={option.depth ? { paddingLeft: 8 + option.depth * 20 } : undefined}
                        >
                            <Checkbox id={id} checked={selected.includes(option.value)} onCheckedChange={(on) => toggle(option.value, on === true)} />
                            <Label htmlFor={id} className="flex-1 cursor-pointer font-normal">
                                {option.label}
                            </Label>
                        </div>
                    );
                })}
            </div>
        );
    }

    return (
        <form
            className="flex flex-col gap-2"
            onSubmit={(event) => {
                event.preventDefault();
                onApply({ [filter.from]: from || null, [filter.to]: to || null });
            }}
        >
            <Label className="flex flex-col gap-1.5 text-xs">
                С
                <Input type="date" value={from} onChange={(event) => setFrom(event.target.value)} className="h-9" />
            </Label>
            <Label className="flex flex-col gap-1.5 text-xs">
                По
                <Input type="date" value={to} onChange={(event) => setTo(event.target.value)} className="h-9" />
            </Label>
            <Button type="submit" size="sm">
                Применить
            </Button>
        </form>
    );
}

function ColumnFilter({ column, filters, onApply }: { column: ColumnDef; filters: Filters; onApply: (changes: Partial<Filters>) => void }) {
    const [open, setOpen] = useState(false);
    const filter = column.filter!;
    const active = isFilterActive(filter, filters);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <button
                    type="button"
                    aria-label={`Фильтр: ${column.label}`}
                    className={cn(
                        'hover:bg-accent hover:text-foreground relative rounded p-1',
                        active ? 'text-brand-strong dark:text-[#C5E27A]' : 'opacity-50 hover:opacity-100',
                    )}
                >
                    <ListFilter className="size-3.5" />
                    {active && <span className="bg-brand absolute top-0.5 right-0.5 size-1.5 rounded-full" />}
                </button>
            </PopoverTrigger>
            <PopoverContent align="start" className={cn('p-3', filter.type === 'multi' && filter.param === 'department' ? 'w-96' : 'w-64')}>
                <div className="mb-2 flex items-center justify-between">
                    <span className="text-sm font-semibold">{column.label}</span>
                    {active && (
                        <button
                            type="button"
                            onClick={() => {
                                onApply(clearedFilter(filter));
                                setOpen(false);
                            }}
                            className="text-muted-foreground hover:text-foreground text-xs"
                        >
                            Сбросить
                        </button>
                    )}
                </div>
                <FilterBody
                    filter={filter}
                    filters={filters}
                    onApply={(changes) => {
                        onApply(changes);
                        if (filter.type !== 'multi') setOpen(false);
                    }}
                />
            </PopoverContent>
        </Popover>
    );
}

/* ---------------------------------------------------------------- page */

export default function Employees({ employees, filters, sort, perPage, perPageOptions, privateAccess, sortable, options }: EmployeesProps) {
    const columns = useMemo(() => buildColumns(options), [options]);
    const defaults = useMemo(() => defaultView(columns, privateAccess), [columns, privateAccess]);
    const [view, setView] = useState<ViewState>(() =>
        loadView(
            defaults,
            columns.map((c) => c.key),
        ),
    );
    const [search, setSearch] = useState(filters.q);
    const firstRender = useRef(true);

    useEffect(() => saveView(view), [view]);

    const visit = (next: { filters?: Partial<Filters>; sort?: Sort; perPage?: number }) => {
        router.get(
            route('employees.index'),
            toParams({ ...filters, ...next.filters }, next.sort ?? sort, next.perPage ?? perPage, perPageOptions[0]),
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

    /* -- column layout: left-pinned, then the rest in order, then right-pinned */
    const isHidden = (key: ColumnKey) => view.hidden.includes(key);
    const pinSide = (key: ColumnKey) => (view.pinned.left.includes(key) ? 'left' : view.pinned.right.includes(key) ? 'right' : null);
    const byKey = (key: ColumnKey) => columns.find((c) => c.key === key)!;

    const left = view.pinned.left.filter((k) => !isHidden(k)).map(byKey);
    const right = view.pinned.right.filter((k) => !isHidden(k)).map(byKey);
    const center = columns.filter((c) => !isHidden(c.key) && !pinSide(c.key));
    const visible = [...left, ...center, ...right];
    const tableWidth = visible.reduce((sum, c) => sum + c.width, 0);

    const stickyStyle = (column: ColumnDef): CSSProperties => {
        const side = pinSide(column.key);
        if (side === 'left') {
            const index = left.indexOf(column);
            return { left: left.slice(0, index).reduce((sum, c) => sum + c.width, 0) };
        }
        if (side === 'right') {
            const index = right.indexOf(column);
            return { right: right.slice(index + 1).reduce((sum, c) => sum + c.width, 0) };
        }
        return {};
    };

    const stickyClass = (column: ColumnDef, header: boolean) => {
        const side = pinSide(column.key);
        if (!side) return '';

        return cn(
            'sticky',
            header ? 'bg-sidebar z-20' : 'bg-card z-[1]',
            side === 'left' && column === left[left.length - 1] && 'shadow-[1px_0_0_var(--border)]',
            side === 'right' && column === right[0] && 'shadow-[-1px_0_0_var(--border)]',
        );
    };

    const pin = (key: ColumnKey, side: 'left' | 'right' | null) =>
        setView((current) => ({
            ...current,
            pinned: {
                left: side === 'left' ? [...current.pinned.left.filter((k) => k !== key), key] : current.pinned.left.filter((k) => k !== key),
                right: side === 'right' ? [key, ...current.pinned.right.filter((k) => k !== key)] : current.pinned.right.filter((k) => k !== key),
            },
        }));

    const toggleHidden = (key: ColumnKey, hidden: boolean) =>
        setView((current) => ({ ...current, hidden: hidden ? [...current.hidden, key] : current.hidden.filter((k) => k !== key) }));

    const canFilter = (column: ColumnDef) => column.filter && (!column.private || privateAccess);
    const activeFilters = columns.filter((c) => canFilter(c) && isFilterActive(c.filter!, filters)).length;

    const sortBy = (key: ColumnKey, direction?: 'asc' | 'desc') =>
        visit({ sort: { key, direction: direction ?? (sort.key === key && sort.direction === 'asc' ? 'desc' : 'asc') } });

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
                                    saveView(null);
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

                <Card className="flex flex-col gap-0 overflow-hidden rounded-xl p-0 md:min-h-0 md:flex-1">
                    <div className="overflow-auto md:min-h-0 md:flex-1">
                        <table className="min-w-full table-fixed border-collapse text-sm" style={{ width: tableWidth }}>
                            <thead className="bg-sidebar sticky top-0 z-30 shadow-[0_1px_0_var(--border)]">
                                <tr className="text-muted-foreground text-left text-[13px] whitespace-nowrap">
                                    {visible.map((column, index) => {
                                        const active = sort.key === column.key;
                                        const canSort = sortable.includes(column.key);
                                        const side = pinSide(column.key);
                                        const SortIcon = !active ? ArrowUpDown : sort.direction === 'asc' ? ArrowUp : ArrowDown;

                                        return (
                                            <th
                                                key={column.key}
                                                scope="col"
                                                aria-sort={active ? (sort.direction === 'asc' ? 'ascending' : 'descending') : undefined}
                                                style={{ width: column.width, ...stickyStyle(column) }}
                                                className={cn(
                                                    'px-4 py-2.5 font-semibold',
                                                    index === 0 && 'pl-6',
                                                    index === visible.length - 1 && 'pr-6',
                                                    stickyClass(column, true),
                                                )}
                                            >
                                                <div className="flex items-center gap-0.5">
                                                    {canSort ? (
                                                        <button
                                                            type="button"
                                                            onClick={() => sortBy(column.key)}
                                                            className={cn(
                                                                'hover:text-foreground -ml-1 inline-flex shrink-0 items-center gap-1.5 rounded px-1 py-0.5',
                                                                active && 'text-foreground',
                                                            )}
                                                        >
                                                            <span>{column.label}</span>
                                                            <SortIcon
                                                                className={cn('size-3.5 shrink-0', !active && 'opacity-40')}
                                                                aria-hidden="true"
                                                            />
                                                        </button>
                                                    ) : (
                                                        <span>{column.label}</span>
                                                    )}

                                                    <div className="ml-auto flex shrink-0 items-center gap-0.5">
                                                        {canFilter(column) && (
                                                            <ColumnFilter column={column} filters={filters} onApply={applyFilters} />
                                                        )}

                                                        <DropdownMenu>
                                                            <DropdownMenuTrigger asChild>
                                                                <button
                                                                    type="button"
                                                                    aria-label={`Действия с колонкой: ${column.label}`}
                                                                    className="hover:bg-accent hover:text-foreground rounded p-1 opacity-50 hover:opacity-100"
                                                                >
                                                                    <EllipsisVertical className="size-3.5" />
                                                                </button>
                                                            </DropdownMenuTrigger>
                                                            <DropdownMenuContent align="end" className="w-52">
                                                                {canSort && (
                                                                    <>
                                                                        <DropdownMenuItem onSelect={() => sortBy(column.key, 'asc')}>
                                                                            <ArrowUp />
                                                                            По возрастанию
                                                                        </DropdownMenuItem>
                                                                        <DropdownMenuItem onSelect={() => sortBy(column.key, 'desc')}>
                                                                            <ArrowDown />
                                                                            По убыванию
                                                                        </DropdownMenuItem>
                                                                        <DropdownMenuSeparator />
                                                                    </>
                                                                )}
                                                                <DropdownMenuRadioGroup
                                                                    value={side ?? 'none'}
                                                                    onValueChange={(value) =>
                                                                        pin(column.key, value === 'none' ? null : (value as 'left' | 'right'))
                                                                    }
                                                                >
                                                                    <DropdownMenuRadioItem value="left">
                                                                        <Pin className="size-4 -rotate-45" />
                                                                        Закрепить слева
                                                                    </DropdownMenuRadioItem>
                                                                    <DropdownMenuRadioItem value="right">
                                                                        <Pin className="size-4 rotate-45" />
                                                                        Закрепить справа
                                                                    </DropdownMenuRadioItem>
                                                                    <DropdownMenuRadioItem value="none">
                                                                        <PinOff className="size-4" />
                                                                        Не закреплять
                                                                    </DropdownMenuRadioItem>
                                                                </DropdownMenuRadioGroup>
                                                                {column.key !== 'name' && (
                                                                    <>
                                                                        <DropdownMenuSeparator />
                                                                        <DropdownMenuItem onSelect={() => toggleHidden(column.key, true)}>
                                                                            <EyeOff />
                                                                            Скрыть колонку
                                                                        </DropdownMenuItem>
                                                                    </>
                                                                )}
                                                            </DropdownMenuContent>
                                                        </DropdownMenu>
                                                    </div>
                                                </div>
                                            </th>
                                        );
                                    })}
                                </tr>
                            </thead>
                            <tbody>
                                {employees.data.map((row) => (
                                    <tr key={row.id} className="border-t align-top">
                                        {visible.map((column, index) => (
                                            <td
                                                key={column.key}
                                                style={stickyStyle(column)}
                                                className={cn(
                                                    'truncate px-4 py-3',
                                                    index === 0 && 'pl-6',
                                                    index === visible.length - 1 && 'pr-6',
                                                    stickyClass(column, false),
                                                )}
                                            >
                                                {column.private && !row.private ? (
                                                    <Lock className="text-muted-foreground/60 size-4" aria-label="Закрытые данные" />
                                                ) : (
                                                    column.cell(row, row.private as PrivateDetails)
                                                )}
                                            </td>
                                        ))}
                                    </tr>
                                ))}

                                {employees.data.length === 0 && (
                                    <tr className="border-t">
                                        <td colSpan={visible.length} className="text-muted-foreground px-6 py-16 text-center">
                                            Никого не нашлось. Попробуйте изменить поиск или фильтры.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="flex shrink-0 flex-wrap items-center gap-x-6 gap-y-2 border-t px-6 py-3">
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
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
