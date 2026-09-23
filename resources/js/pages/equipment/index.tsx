import {
    clearedFilter,
    countActiveFilters,
    DataTable,
    resetView,
    useTableView,
    type ColumnDef,
    type Sort,
    type ViewState,
} from '@/components/data-table';
import InputError from '@/components/input-error';
import { Pagination, type Paginated } from '@/components/pagination';
import { PersonAvatar } from '@/components/person-avatar';
import { SearchableSelect } from '@/components/searchable-select';
import { StatusBadge, type StatusTone } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
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
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/employee';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowDownToLine,
    ChevronDown,
    Columns3,
    Ellipsis,
    Headphones,
    Laptop,
    LoaderCircle,
    Monitor,
    Package,
    Plus,
    Printer,
    RotateCcw,
    Search,
    Smartphone,
    Trash2,
    UserPlus,
    Users,
    Wrench,
    X,
    type LucideIcon,
} from 'lucide-react';
import { useEffect, useMemo, useState, type FormEventHandler } from 'react';

type Status = 'issued' | 'stock' | 'repair' | 'written_off';

/** The colours the design gives each status; they are the app's own tones. */
const statusTone: Record<Status, StatusTone> = {
    issued: 'success',
    stock: 'neutral',
    repair: 'warning',
    written_off: 'danger',
};

const statusLabel: Record<Status, string> = {
    issued: 'Выдано',
    stock: 'На складе',
    repair: 'В ремонте',
    written_off: 'Списано',
};

/** An icon per category, as the design draws them; anything else gets a box. */
const categoryIcon: Record<string, LucideIcon> = {
    Ноутбуки: Laptop,
    Мониторы: Monitor,
    Телефоны: Smartphone,
    Печать: Printer,
    Периферия: Headphones,
};

/** The tinted square an icon sits in, in the tiles and beside every name. */
function IconChip({ icon: Icon, tone = 'neutral', size = 36 }: { icon: LucideIcon; tone?: 'brand' | 'warning' | 'neutral'; size?: number }) {
    const tones = {
        brand: 'bg-[#EEF5DC] text-[#4A6410] dark:bg-[#A8CF45]/15 dark:text-[#C5E27A]',
        warning: 'bg-[#FBEFD9] text-[#9A4A06] dark:bg-[#F5A524]/15 dark:text-[#F8C471]',
        neutral: 'bg-[#F4F4F5] text-[#44474C] dark:bg-white/10 dark:text-neutral-300',
    };

    return (
        <span
            aria-hidden="true"
            style={{ width: size, height: size }}
            className={cn('flex shrink-0 items-center justify-center rounded-lg', tones[tone])}
        >
            <Icon className="size-[18px]" />
        </span>
    );
}

interface Unit {
    id: number;
    name: string;
    maker: string | null;
    serial_number: string | null;
    inventory_number: string;
    type: string | null;
    status: Status;
    /** Set when the unit is with one person rather than a department. */
    holder: { id: number; name: string; avatar: string | null } | null;
    department: string | null;
    issued_at: string | null;
    written_off_at: string | null;
}

interface Filters {
    q: string;
    name: string;
    inventory_number: string;
    type: number[];
    status: Status[];
    /** A name typed in, not a pick from a list: colleague or department. */
    holder: string;
    issued_from: string | null;
    issued_to: string | null;
}

interface Options {
    types: { id: number; name: string }[];
    statuses: { value: Status; label: string }[];
    holders: { id: number; name: string }[];
    departments: { id: number; name: string }[];
}

interface Props {
    equipment: Paginated<Unit>;
    filters: Filters;
    /** The status tab above the table; null is "Все". */
    tab: Status | null;
    sort: Sort;
    sortable: string[];
    perPage: number;
    perPageOptions: number[];
    counts: Record<'all' | Status, number>;
    summary: { total: number; issued: number; stock: number; repair: number; issued_share: number };
    options: Options;
    canEdit: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Оборудование', href: '/equipment' }];

const STORAGE_KEY = 'equipment.table.view.v1';
const DEFAULT_SORT: Sort = { key: 'name', direction: 'asc' };

/** An icon, what it counts, and the number itself at the right edge. */
function Tile({ label, value, icon, tone }: { label: string; value: number; icon: LucideIcon; tone?: 'brand' | 'warning' | 'neutral' }) {
    return (
        <Card className="flex items-center gap-3 rounded-[14px] px-5 py-4">
            <IconChip icon={icon} tone={tone} size={36} />
            <span className="text-muted-foreground min-w-0 flex-1 truncate text-sm font-medium">{label}</span>
            <span className="text-[30px] leading-none font-semibold tabular-nums">{value}</span>
        </Card>
    );
}

/** What a unit can be moved to next, given where it is now. */
type Move = 'issue' | 'take' | 'repair' | 'write-off';

const moveLabel: Record<Move, string> = {
    issue: 'Выдать',
    take: 'Принять возврат',
    repair: 'Отправить в ремонт',
    'write-off': 'Списать',
};

/**
 * The two moves that need something from HR: whom a unit goes to, and when it
 * was written off. Returning it and sending it for repair ask nothing.
 */
type AskedMove = 'issue' | 'write-off';

/** The "⋯" at the end of a row; a written-off unit has nowhere left to go. */
function RowActions({ unit, onAsk }: { unit: Unit; onAsk: (move: { unit: Unit; kind: AskedMove }) => void }) {
    const run = (kind: Move) => router.post(route(`equipment.${kind}`, unit.id), {}, { preserveScroll: true });
    const pick = (kind: Move) => (kind === 'issue' || kind === 'write-off' ? onAsk({ unit, kind }) : run(kind));

    if (unit.status === 'written_off') return null;

    const moves: { kind: Move; icon: LucideIcon }[] = [
        ...(unit.status === 'issued' ? [{ kind: 'take' as const, icon: ArrowDownToLine }] : [{ kind: 'issue' as const, icon: UserPlus }]),
        ...(unit.status === 'repair' ? [] : [{ kind: 'repair' as const, icon: Wrench }]),
        { kind: 'write-off' as const, icon: Trash2 },
    ];

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="text-muted-foreground size-8" aria-label={`Действия: ${unit.name}`}>
                    <Ellipsis className="size-5!" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
                {moves.map(({ kind, icon: Icon }) => (
                    <DropdownMenuItem
                        key={kind}
                        onSelect={() => pick(kind)}
                        className={cn(kind === 'write-off' && 'text-[#B42318] focus:text-[#B42318] dark:text-[#F7A19A] [&_svg]:text-current!')}
                    >
                        <Icon />
                        {moveLabel[kind]}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

/** Who has it: a colleague or a whole department; in stock, nobody. */
function Holder({ unit }: { unit: Unit }) {
    if (unit.holder) {
        return (
            <Link href={route('employees.show', unit.holder.id)} className="flex items-center gap-2 hover:underline">
                {unit.holder.avatar ? (
                    <img src={unit.holder.avatar} alt="" className="size-7 shrink-0 rounded-full object-cover" />
                ) : (
                    <PersonAvatar name={unit.holder.name} className="size-7 text-[11px]" />
                )}
                <span className="truncate">{unit.holder.name}</span>
            </Link>
        );
    }

    return unit.department ? <span className="truncate">{unit.department}</span> : <span className="text-muted-foreground">—</span>;
}

function MoveDialog({ unit, kind, options, onClose }: { unit: Unit; kind: AskedMove; options: Options; onClose: () => void }) {
    const today = new Date().toISOString().slice(0, 10);
    const form = useForm({ holder_user_id: '', issued_at: today, written_off_at: today });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.transform((data) =>
            kind === 'issue' ? { holder_user_id: data.holder_user_id, issued_at: data.issued_at } : { written_off_at: data.written_off_at },
        );
        form.post(route(`equipment.${kind}`, unit.id), { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-md">
                {/* noValidate: the server's rules are the real ones. */}
                <form onSubmit={submit} noValidate className="flex flex-col gap-5">
                    <DialogHeader>
                        <DialogTitle>{moveLabel[kind]}</DialogTitle>
                        <DialogDescription>
                            {unit.name} · инв. № {unit.inventory_number}
                        </DialogDescription>
                    </DialogHeader>

                    {kind === 'issue' && (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="move-holder">Кому</Label>
                                <SearchableSelect
                                    id="move-holder"
                                    value={form.data.holder_user_id}
                                    onChange={(value) => form.setData('holder_user_id', value)}
                                    options={options.holders.map((holder) => ({ value: String(holder.id), label: holder.name }))}
                                    placeholder="Выберите сотрудника"
                                    searchPlaceholder="Поиск по фамилии или имени"
                                    empty="Сотрудник не найден"
                                    invalid={!!form.errors.holder_user_id}
                                />
                                <InputError message={form.errors.holder_user_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="move-issued">Дата выдачи</Label>
                                <Input
                                    id="move-issued"
                                    type="date"
                                    max={today}
                                    value={form.data.issued_at}
                                    onChange={(e) => form.setData('issued_at', e.target.value)}
                                    aria-invalid={!!form.errors.issued_at}
                                />
                                <InputError message={form.errors.issued_at} />
                            </div>
                        </>
                    )}

                    {kind === 'write-off' && (
                        <div className="grid gap-2">
                            <Label htmlFor="move-written-off">Дата списания</Label>
                            <Input
                                id="move-written-off"
                                type="date"
                                max={today}
                                value={form.data.written_off_at}
                                onChange={(e) => form.setData('written_off_at', e.target.value)}
                                aria-invalid={!!form.errors.written_off_at}
                            />
                            <InputError message={form.errors.written_off_at} />
                            <p className="text-muted-foreground text-[13px]">Списанную единицу больше нельзя выдать или отремонтировать.</p>
                        </div>
                    )}

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={onClose}>
                            Отмена
                        </Button>
                        <Button type="submit" variant={kind === 'write-off' ? 'destructive' : 'default'} disabled={form.processing}>
                            {form.processing && <LoaderCircle className="animate-spin" />}
                            {moveLabel[kind]}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/**
 * Putting a unit on the books. It starts in stock — handing it to somebody is
 * a move of its own, from the "⋯" beside the row.
 */
function AddDialog({ options, onClose }: { options: Options; onClose: () => void }) {
    const form = useForm({ equipment_type_id: '', name: '', maker: '', serial_number: '', inventory_number: '' });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.post(route('equipment.store'), { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-lg">
                {/* noValidate: the server's rules are the real ones. */}
                <form onSubmit={submit} noValidate className="flex flex-col gap-5">
                    <DialogHeader>
                        <DialogTitle>Добавить оборудование</DialogTitle>
                        <DialogDescription>Единица встаёт на баланс со статусом «На складе».</DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="add-name">Наименование</Label>
                        <Input
                            id="add-name"
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                            placeholder="Ноутбук Dell Latitude 5440"
                            aria-invalid={!!form.errors.name}
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="add-type">Категория</Label>
                        <SearchableSelect
                            id="add-type"
                            value={form.data.equipment_type_id}
                            onChange={(value) => form.setData('equipment_type_id', value)}
                            options={options.types.map((type) => ({ value: String(type.id), label: type.name }))}
                            placeholder="Выберите категорию"
                            searchPlaceholder="Поиск категории"
                            empty="Категория не найдена"
                            invalid={!!form.errors.equipment_type_id}
                        />
                        <InputError message={form.errors.equipment_type_id} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="add-maker">Производитель</Label>
                            <Input
                                id="add-maker"
                                value={form.data.maker}
                                onChange={(event) => form.setData('maker', event.target.value)}
                                placeholder="Dell"
                                aria-invalid={!!form.errors.maker}
                            />
                            <InputError message={form.errors.maker} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="add-serial">Серийный номер</Label>
                            <Input
                                id="add-serial"
                                value={form.data.serial_number}
                                onChange={(event) => form.setData('serial_number', event.target.value)}
                                placeholder="7K2L9P3"
                                aria-invalid={!!form.errors.serial_number}
                            />
                            <InputError message={form.errors.serial_number} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="add-inventory">Инвентарный номер</Label>
                        <Input
                            id="add-inventory"
                            value={form.data.inventory_number}
                            onChange={(event) => form.setData('inventory_number', event.target.value)}
                            placeholder="EV-0421"
                            aria-invalid={!!form.errors.inventory_number}
                        />
                        <InputError message={form.errors.inventory_number} />
                        <p className="text-muted-foreground text-[13px]">Номер на наклейке; у каждой единицы он свой.</p>
                    </div>

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={onClose}>
                            Отмена
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <LoaderCircle className="animate-spin" />}
                            Добавить
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/** The columns, each with the filter that narrows it. */
function buildColumns(options: Options): ColumnDef[] {
    return [
        {
            key: 'name',
            label: 'Наименование',
            width: 380,
            filter: { type: 'text', param: 'name', placeholder: 'Название модели' },
        },
        {
            key: 'inventory_number',
            label: 'Инв. номер',
            width: 160,
            filter: { type: 'text', param: 'inventory_number', placeholder: 'EV-0421' },
        },
        {
            key: 'type',
            label: 'Категория',
            width: 180,
            filter: { type: 'multi', param: 'type', options: options.types.map((t) => ({ value: t.id, label: t.name })) },
        },
        {
            key: 'status',
            label: 'Статус',
            width: 160,
            filter: { type: 'multi', param: 'status', options: options.statuses.map((s) => ({ value: s.value, label: s.label })) },
        },
        {
            key: 'holder',
            label: 'У кого',
            width: 250,
            filter: { type: 'text', param: 'holder', placeholder: 'Фамилия или отдел' },
        },
        {
            key: 'issued_at',
            label: 'Выдано',
            width: 170,
            filter: { type: 'dates', from: 'issued_from', to: 'issued_to' },
        },
    ];
}

const defaultView = (): ViewState => ({ hidden: [], pinned: { left: ['name'], right: [] } });

export default function EquipmentIndex({
    equipment,
    filters,
    tab,
    sort,
    sortable,
    perPage,
    perPageOptions,
    counts,
    summary,
    options,
    canEdit,
}: Props) {
    const columns = useMemo(() => buildColumns(options), [options]);
    const defaults = useMemo(defaultView, []);
    const { view, setView, pin, toggleHidden } = useTableView(
        STORAGE_KEY,
        columns.map((column) => column.key),
        defaults,
    );
    const isHidden = (key: string) => view.hidden.includes(key);

    const [query, setQuery] = useState(filters.q);
    const [asking, setAsking] = useState<{ unit: Unit; kind: AskedMove } | null>(null);
    const [adding, setAdding] = useState(false);

    /** Everything the list is looking at, as one query string. */
    const visit = (next: { filters?: Partial<Filters>; sort?: Sort; perPage?: number; tab?: Status | null }) => {
        const merged = { ...filters, ...next.filters };
        const nextSort = next.sort ?? sort;
        const nextPer = next.perPage ?? perPage;
        const nextTab = next.tab === undefined ? tab : next.tab;

        type Value = string | number | (string | number)[] | null;
        const params: Record<string, Value> = {
            ...merged,
            tab: nextTab,
            sort: nextSort.key === DEFAULT_SORT.key ? null : nextSort.key,
            direction: nextSort.direction === DEFAULT_SORT.direction ? null : nextSort.direction,
            per_page: nextPer === perPageOptions[0] ? null : nextPer,
        };

        // An empty filter is left out rather than sent as a blank.
        const query = Object.fromEntries(
            Object.entries(params).filter(([, value]) => (Array.isArray(value) ? value.length > 0 : value !== null && value !== '')),
        ) as Record<string, Exclude<Value, null>>;

        router.get(route('equipment.index'), query, { preserveState: true, preserveScroll: true, replace: true });
    };

    // Typing searches on its own, once the typing stops.
    useEffect(() => setQuery(filters.q), [filters.q]);
    useEffect(() => {
        if (query === filters.q) return;
        const timer = setTimeout(() => visit({ filters: { q: query } }), 300);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [query]);

    const tabs: { key: Status | null; label: string; count: number }[] = [
        { key: null, label: 'Все', count: counts.all },
        ...(['issued', 'stock', 'repair', 'written_off'] as Status[]).map((key) => ({ key, label: statusLabel[key], count: counts[key] })),
    ];

    const activeFilters = countActiveFilters(columns, filters as unknown as Record<string, unknown>, () => true);

    const cell = (column: ColumnDef, unit: Unit) => {
        switch (column.key) {
            case 'name':
                return (
                    <div className="flex items-center gap-3">
                        <IconChip icon={(unit.type && categoryIcon[unit.type]) || Package} />
                        <div className="flex min-w-0 flex-col gap-0.5">
                            <span className="truncate font-medium">{unit.name}</span>
                            <span className="text-muted-foreground truncate text-[13px]">
                                {[unit.maker, unit.serial_number && `S/N ${unit.serial_number}`].filter(Boolean).join(' · ')}
                            </span>
                        </div>
                    </div>
                );
            case 'inventory_number':
                return <span className="tabular-nums">{unit.inventory_number}</span>;
            case 'type':
                return <span className="text-muted-foreground">{unit.type ?? '—'}</span>;
            case 'status':
                return <StatusBadge tone={statusTone[unit.status]}>{statusLabel[unit.status]}</StatusBadge>;
            case 'holder':
                return <Holder unit={unit} />;
            default:
                return (
                    <span className="text-muted-foreground tabular-nums">
                        {unit.status === 'written_off' ? `Списан ${formatDate(unit.written_off_at)}` : (formatDate(unit.issued_at) ?? '—')}
                    </span>
                );
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs} fitViewport>
            <Head title="Оборудование" />

            <div className="flex flex-1 flex-col gap-4 p-3 md:min-h-0 md:px-5 md:py-4">
                <h1 className="text-xl font-semibold tracking-tight">Оборудование</h1>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Tile label="Всего единиц" value={summary.total} icon={Package} tone="brand" />
                    <Tile label="Выдано" value={summary.issued} icon={Users} tone="brand" />
                    <Tile label="На складе" value={summary.stock} icon={Laptop} />
                    <Tile label="В ремонте" value={summary.repair} icon={Wrench} tone="warning" />
                </div>

                {/* Search, the status lists, the view and the one thing you can add: one line. */}
                <div className="-mb-2 flex flex-wrap items-center gap-2">
                    <label className="border-input bg-background text-muted-foreground focus-within:ring-ring flex h-8 min-w-48 flex-1 items-center gap-2 rounded-md border px-3 shadow-xs focus-within:ring-2">
                        <Search className="size-4 shrink-0" />
                        <span className="sr-only">Поиск по всем полям</span>
                        <input
                            type="search"
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder="Поиск по всем полям"
                            className="text-foreground min-w-0 flex-1 bg-transparent text-sm outline-hidden"
                        />
                    </label>

                    <nav aria-label="Статус оборудования" className="flex flex-wrap items-center gap-1 text-sm">
                        {tabs.map((item) => {
                            const active = tab === item.key;

                            return (
                                <button
                                    key={item.key ?? 'all'}
                                    type="button"
                                    onClick={() => visit({ tab: item.key })}
                                    aria-current={active ? 'page' : undefined}
                                    className={cn(
                                        'flex h-8 items-center gap-1.5 rounded-md px-2.5 transition-colors',
                                        active
                                            ? 'bg-brand-soft text-foreground font-semibold dark:bg-white/10'
                                            : 'text-muted-foreground hover:bg-accent hover:text-foreground',
                                    )}
                                >
                                    {item.label}
                                    <span className="text-muted-foreground text-xs font-semibold tabular-nums">{item.count}</span>
                                </button>
                            );
                        })}
                    </nav>

                    {activeFilters > 0 && (
                        <Button
                            variant="ghost"
                            className="h-8"
                            onClick={() =>
                                visit({
                                    filters: columns.reduce<Partial<Filters>>((acc, column) => ({ ...acc, ...clearedFilter(column.filter!) }), {}),
                                })
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

                    {canEdit && (
                        <Button className="h-8" onClick={() => setAdding(true)}>
                            <Plus />
                            Добавить оборудование
                        </Button>
                    )}
                </div>

                <DataTable
                    columns={columns}
                    rows={equipment.data}
                    rowKey={(unit) => unit.id}
                    renderCell={cell}
                    sort={sort}
                    sortable={sortable}
                    onSort={(key, direction) =>
                        visit({ sort: { key, direction: direction ?? (sort.key === key && sort.direction === 'asc' ? 'desc' : 'asc') } })
                    }
                    filters={filters as unknown as Record<string, unknown>}
                    onFilter={(changes) => visit({ filters: changes as Partial<Filters> })}
                    view={view}
                    onPin={pin}
                    onHide={(key) => toggleHidden(key, true)}
                    lockedKey="name"
                    actions={canEdit ? (unit) => <RowActions unit={unit} onAsk={setAsking} /> : undefined}
                    empty="Ничего не найдено."
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
                            <Pagination paginator={equipment} className="min-w-0 flex-1" />
                        </>
                    }
                />
            </div>

            {asking && <MoveDialog unit={asking.unit} kind={asking.kind} options={options} onClose={() => setAsking(null)} />}
            {adding && <AddDialog options={options} onClose={() => setAdding(false)} />}
        </AppLayout>
    );
}
