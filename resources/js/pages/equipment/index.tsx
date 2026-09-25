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
import { CategoryChip } from '@/components/equipment-icon';
import { EquipmentMoveDialog, moveLabel, type AskedMove } from '@/components/equipment-move-dialog';
import { Pagination, type Paginated } from '@/components/pagination';
import { PersonAvatar } from '@/components/person-avatar';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
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
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/employee';
import { statusLabel, statusTone, type EquipmentStatus as Status } from '@/lib/equipment';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowDownToLine,
    ChevronDown,
    Columns3,
    Ellipsis,
    Eraser,
    History,
    LoaderCircle,
    Plus,
    RotateCcw,
    Search,
    Trash2,
    UserPlus,
    Wrench,
    X,
    type LucideIcon,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

interface Unit {
    id: number;
    name: string;
    maker: string | null;
    serial_number: string | null;
    inventory_number: string;
    type: string | null;
    status: Status;
    /** Null while nobody holds it. */
    holder: { id: number; name: string; avatar: string | null } | null;
    issued_at: string | null;
    written_off_at: string | null;
    /** A piece of service work on it has not ended yet. */
    in_service: boolean;
}

/** The tab above the table: a status, the units being serviced, or everything. */
type Tab = Status | 'service' | null;

interface Filters {
    q: string;
    name: string;
    inventory_number: string;
    type: number[];
    status: Status[];
    /** A name typed in, not a pick from a list. */
    holder: string;
    issued_from: string | null;
    issued_to: string | null;
}

interface Options {
    types: { id: number; name: string }[];
    statuses: { value: Status; label: string }[];
    holders: { id: number; name: string }[];
}

interface Props {
    equipment: Paginated<Unit>;
    filters: Filters;
    /** The tab above the table; null is "Все", "service" is not a status. */
    tab: Tab;
    sort: Sort;
    sortable: string[];
    perPage: number;
    perPageOptions: number[];
    counts: Record<'all' | 'service' | Status, number>;
    options: Options;
    canEdit: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Оборудование', href: '/equipment' }];

const STORAGE_KEY = 'equipment.table.view.v1';
const DEFAULT_SORT: Sort = { key: 'name', direction: 'asc' };

/** Red, for the one action that cannot be undone. */
const dangerItem = 'text-[#B42318] focus:text-[#B42318] dark:text-[#F7A19A] [&_svg]:text-current!';

/**
 * The "⋯" at the end of a row. A written-off unit has nowhere left to go: the
 * only thing left to do with it is strike it off the books for good.
 */
function RowActions({
    unit,
    onAsk,
    onDelete,
}: {
    unit: Unit;
    onAsk: (move: { unit: Unit; kind: AskedMove }) => void;
    onDelete: (unit: Unit) => void;
}) {
    if (unit.status === 'written_off') {
        return (
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="icon" className="text-muted-foreground size-8" aria-label={`Действия: ${unit.name}`}>
                        <Ellipsis className="size-5!" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-56">
                    <DropdownMenuItem onSelect={() => onDelete(unit)} className={dangerItem}>
                        <Eraser />
                        Удалить запись…
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        );
    }

    const moves: { kind: AskedMove; icon: LucideIcon }[] = [
        ...(unit.status === 'issued' ? [{ kind: 'take' as const, icon: ArrowDownToLine }] : [{ kind: 'issue' as const, icon: UserPlus }]),
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
                    <DropdownMenuItem key={kind} onSelect={() => onAsk({ unit, kind })} className={cn(kind === 'write-off' && dangerItem)}>
                        <Icon />
                        {moveLabel[kind]}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

/**
 * Striking a unit off the books for good — a duplicate, or something entered
 * by mistake. Everything filed under it goes too, so the dialog says as much
 * before it asks.
 */
function DeleteDialog({ unit, onClose }: { unit: Unit; onClose: () => void }) {
    const [busy, setBusy] = useState(false);

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Удалить запись?</DialogTitle>
                    <DialogDescription>
                        {unit.name} · инв. № {unit.inventory_number}
                    </DialogDescription>
                </DialogHeader>

                <p className="text-sm">
                    Вместе с единицей исчезнут её история передач, ремонты, документы и журнал. Отменить это нельзя. Если техника просто отслужила
                    своё — её нужно <span className="font-medium">списать</span>, а не удалять.
                </p>

                <DialogFooter className="gap-2">
                    <Button type="button" variant="outline" onClick={onClose}>
                        Отмена
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        disabled={busy}
                        onClick={() => {
                            setBusy(true);
                            router.delete(route('equipment.destroy', unit.id), { onFinish: onClose });
                        }}
                    >
                        {busy && <LoaderCircle className="animate-spin" />}
                        Удалить
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

/** Who has it; on the balance sheet, nobody. */
function Holder({ unit }: { unit: Unit }) {
    if (unit.holder) {
        return (
            <Link
                href={route('employees.show', unit.holder.id)}
                title={`Открыть профиль: ${unit.holder.name}`}
                className="text-brand-strong flex items-center gap-2 hover:underline dark:text-[#C5E27A]"
            >
                {unit.holder.avatar ? (
                    <img src={unit.holder.avatar} alt="" className="size-7 shrink-0 rounded-full object-cover" />
                ) : (
                    <PersonAvatar name={unit.holder.name} className="size-7 text-[11px]" />
                )}
                <span className="truncate">{unit.holder.name}</span>
            </Link>
        );
    }

    return <span className="text-muted-foreground">—</span>;
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
            filter: { type: 'text', param: 'holder', placeholder: 'Фамилия сотрудника' },
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

export default function EquipmentIndex({ equipment, filters, tab, sort, sortable, perPage, perPageOptions, counts, options, canEdit }: Props) {
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
    const [deleting, setDeleting] = useState<Unit | null>(null);

    /** Everything the list is looking at, as one query string. */
    const visit = (next: { filters?: Partial<Filters>; sort?: Sort; perPage?: number; tab?: Tab }) => {
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

    const tabs: { key: Tab; label: string; count: number }[] = [
        { key: null, label: 'Все', count: counts.all },
        ...(['issued', 'stock'] as Status[]).map((key) => ({ key, label: statusLabel[key], count: counts[key] })),
        // Not a status: a unit can be with its owner and on service at once.
        { key: 'service', label: 'На обслуживании', count: counts.service },
        { key: 'written_off', label: statusLabel.written_off, count: counts.written_off },
    ];

    const activeFilters = countActiveFilters(columns, filters as unknown as Record<string, unknown>, () => true);

    const cell = (column: ColumnDef, unit: Unit) => {
        switch (column.key) {
            case 'name':
                return (
                    <Link href={route('equipment.show', unit.id)} className="group flex items-center gap-3" title={`Открыть: ${unit.name}`}>
                        <CategoryChip type={unit.type} />
                        <div className="flex min-w-0 flex-col gap-0.5">
                            {/* Brand colour and an underline on hover: the app's mark of a link. */}
                            <span className="text-brand-strong truncate font-medium group-hover:underline dark:text-[#C5E27A]">{unit.name}</span>
                            <span className="text-muted-foreground truncate text-[13px]">
                                {[unit.maker, unit.serial_number && `S/N ${unit.serial_number}`].filter(Boolean).join(' · ')}
                            </span>
                        </div>
                    </Link>
                );
            case 'inventory_number':
                return <span className="tabular-nums">{unit.inventory_number}</span>;
            case 'type':
                return <span className="text-muted-foreground">{unit.type ?? '—'}</span>;
            case 'status':
                return (
                    <span className="flex items-center gap-1.5">
                        <StatusBadge tone={statusTone[unit.status]}>{statusLabel[unit.status]}</StatusBadge>
                        {unit.in_service && <Wrench className="text-muted-foreground size-4 shrink-0" aria-label="На обслуживании" />}
                    </span>
                );
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
                        <>
                            <Button variant="outline" className="h-8" asChild>
                                <Link href={route('equipment.journal')}>
                                    <History />
                                    Журнал
                                </Link>
                            </Button>

                            <Button className="h-8" asChild>
                                <Link href={route('equipment.create')}>
                                    <Plus />
                                    Добавить оборудование
                                </Link>
                            </Button>
                        </>
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
                    actions={canEdit ? (unit) => <RowActions unit={unit} onAsk={setAsking} onDelete={setDeleting} /> : undefined}
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

            {asking && <EquipmentMoveDialog unit={asking.unit} kind={asking.kind} holders={options.holders} onClose={() => setAsking(null)} />}
            {deleting && <DeleteDialog unit={deleting} onClose={() => setDeleting(null)} />}
        </AppLayout>
    );
}
