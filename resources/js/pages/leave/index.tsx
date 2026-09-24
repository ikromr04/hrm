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
import { Check, ChevronDown, Columns3, Ellipsis, Plus, RotateCcw, Undo2, X } from 'lucide-react';
import { useMemo, useState, type FormEventHandler } from 'react';

type Status = 'pending_head' | 'pending_hr' | 'approved' | 'rejected' | 'cancelled';

const statusLabel: Record<Status, string> = {
    pending_head: 'У руководителя',
    pending_hr: 'У HR-отдела',
    approved: 'Одобрено',
    rejected: 'Отклонено',
    cancelled: 'Отозвано',
};

const statusTone: Record<Status, StatusTone> = {
    pending_head: 'warning',
    pending_hr: 'warning',
    approved: 'success',
    rejected: 'danger',
    cancelled: 'neutral',
};

interface LeaveRow {
    id: number;
    employee: { id: number; name: string; avatar: string | null };
    type: { name: string; tone: StatusTone };
    started_on: string;
    ended_on: string;
    days: number;
    note: string | null;
    status: Status;
    decision_note: string | null;
    /** Worked out on the server: this viewer's part in this request. */
    can: { decide: boolean; cancel: boolean };
}

interface Balance {
    id: number;
    name: string;
    tone: StatusTone;
    /** Null for a kind with no yearly allowance, such as unpaid leave. */
    days_per_year: number | null;
    max_part_days: number | null;
    used: number;
    left: number | null;
}

interface LeaveType {
    id: number;
    name: string;
    days_per_year: number | null;
    max_part_days: number | null;
    tone: StatusTone;
}

interface Filters {
    type: number[];
    employee: string;
    from: string | null;
    to: string | null;
}

type Tab = 'open' | 'approved' | 'rejected';

interface Props {
    requests: Paginated<LeaveRow>;
    filters: Filters;
    tab: Tab | null;
    year: number;
    perPage: number;
    perPageOptions: number[];
    balances: Balance[];
    types: LeaveType[];
    counts: Record<'all' | Tab, number>;
    /** Heads and HR: they see everybody's requests and decide on them. */
    decides: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Отпуска', href: '/leave' }];

const STORAGE_KEY = 'leave.table.view.v1';
const DEFAULT_SORT: Sort = { key: 'started_on', direction: 'desc' };

/** Days between two dates, both ends counted — what the server will store. */
const daysBetween = (from: string, to: string) => {
    if (!from || !to) return 0;

    const start = new Date(from);
    const end = new Date(to);
    const days = Math.round((end.getTime() - start.getTime()) / 86400000) + 1;

    return days > 0 ? days : 0;
};

/** One kind of time off: how much is left of it, and how much is spent. */
function BalanceTile({ balance }: { balance: Balance }) {
    const share = balance.days_per_year ? Math.min(100, Math.round((balance.used / balance.days_per_year) * 100)) : 0;

    return (
        <Card className="flex flex-col gap-3 rounded-[14px] p-5">
            <div className="flex items-center gap-2">
                <span className="text-muted-foreground min-w-0 flex-1 truncate text-sm font-medium">{balance.name}</span>
                <span className="text-muted-foreground text-[13px]">
                    {balance.days_per_year === null ? 'без нормы' : `из ${balance.days_per_year} дн.`}
                </span>
            </div>

            <div className="flex items-baseline gap-2">
                <span className="text-[32px] leading-none font-bold tabular-nums">{balance.left ?? '—'}</span>
                <span className="text-muted-foreground text-sm">дн. доступно</span>
            </div>

            <div className="bg-muted h-2 overflow-hidden rounded-full">
                <div className="bg-brand h-2 rounded-full" style={{ width: `${share}%` }} />
            </div>

            <span className="text-muted-foreground text-[13px]">Использовано {balance.used} дн.</span>
        </Card>
    );
}

/** Asking for time off: a kind, a first and a last day, and why. */
function RequestDialog({ types, balances, onClose }: { types: LeaveType[]; balances: Balance[]; onClose: () => void }) {
    const form = useForm({ leave_type_id: '', started_on: '', ended_on: '', note: '' });

    const chosen = types.find((type) => String(type.id) === form.data.leave_type_id) ?? null;
    const balance = balances.find((item) => item.id === chosen?.id) ?? null;
    const days = daysBetween(form.data.started_on, form.data.ended_on);

    // The same rules the server keeps, said before the trip rather than after.
    const tooLong = chosen?.max_part_days != null && days > chosen.max_part_days;
    const overBalance = balance?.left != null && days > balance.left;

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.post(route('leave.store'), { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-lg">
                {/* noValidate: the server's rules are the real ones. */}
                <form onSubmit={submit} noValidate className="flex flex-col gap-5">
                    <DialogHeader>
                        <DialogTitle>Новая заявка</DialogTitle>
                        <DialogDescription>Заявка уйдёт руководителю, после согласования — в HR-отдел.</DialogDescription>
                    </DialogHeader>

                    <div className="grid content-start gap-2">
                        <Label htmlFor="leave-type">Вид отсутствия</Label>
                        <SearchableSelect
                            id="leave-type"
                            value={form.data.leave_type_id}
                            onChange={(value) => form.setData('leave_type_id', value)}
                            options={types.map((type) => ({
                                value: String(type.id),
                                label: type.name,
                                hint: type.days_per_year === null ? 'без годовой нормы' : `${type.days_per_year} дн. в году`,
                            }))}
                            placeholder="Выберите вид"
                            searchPlaceholder="Поиск вида"
                            empty="Ничего не найдено"
                            invalid={!!form.errors.leave_type_id}
                        />
                        <InputError message={form.errors.leave_type_id} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid content-start gap-2">
                            <Label htmlFor="leave-from">Первый день</Label>
                            <Input
                                id="leave-from"
                                type="date"
                                value={form.data.started_on}
                                onChange={(event) => form.setData('started_on', event.target.value)}
                                aria-invalid={!!form.errors.started_on}
                            />
                            <InputError message={form.errors.started_on} />
                        </div>

                        <div className="grid content-start gap-2">
                            <Label htmlFor="leave-to">Последний день</Label>
                            <Input
                                id="leave-to"
                                type="date"
                                min={form.data.started_on || undefined}
                                value={form.data.ended_on}
                                onChange={(event) => form.setData('ended_on', event.target.value)}
                                aria-invalid={!!form.errors.ended_on}
                            />
                            <InputError message={form.errors.ended_on} />
                        </div>
                    </div>

                    {days > 0 && (
                        <div
                            className={cn(
                                'flex flex-col gap-1 rounded-lg px-4 py-3 text-sm',
                                tooLong || overBalance ? 'bg-[#FDE8E6] text-[#B42318] dark:bg-[#B42318]/15 dark:text-[#F7A19A]' : 'bg-muted',
                            )}
                        >
                            <span className="font-semibold">{days} календ. дн.</span>
                            <span className="text-[13px]">
                                {tooLong
                                    ? `«${chosen?.name}» нельзя брать больше ${chosen?.max_part_days} дн. подряд.`
                                    : overBalance
                                      ? `Остаток — ${balance?.left} дн., этого не хватит.`
                                      : balance?.left != null
                                        ? `После этой заявки останется ${balance.left - days} дн.`
                                        : 'У этого вида нет годовой нормы.'}
                            </span>
                        </div>
                    )}

                    <div className="grid content-start gap-2">
                        <Label htmlFor="leave-note">Комментарий</Label>
                        <Input
                            id="leave-note"
                            value={form.data.note}
                            onChange={(event) => form.setData('note', event.target.value)}
                            placeholder="Остаюсь на связи по срочным вопросам"
                            aria-invalid={!!form.errors.note}
                        />
                        <InputError message={form.errors.note} />
                    </div>

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={onClose}>
                            Отмена
                        </Button>
                        <Button type="submit" disabled={form.processing || days === 0 || tooLong || overBalance}>
                            Отправить
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/** Turning a request down takes a reason. */
function RejectDialog({ leave, onClose }: { leave: LeaveRow; onClose: () => void }) {
    const form = useForm({ decision_note: '' });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.post(route('leave.reject', leave.id), { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-md">
                {/* noValidate: the server's rules are the real ones. */}
                <form onSubmit={submit} noValidate className="flex flex-col gap-5">
                    <DialogHeader>
                        <DialogTitle>Отклонить заявку</DialogTitle>
                        <DialogDescription>
                            {leave.employee.name} · {formatDate(leave.started_on)} – {formatDate(leave.ended_on)}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid content-start gap-2">
                        <Label htmlFor="reject-note">Причина</Label>
                        <Input
                            id="reject-note"
                            autoFocus
                            value={form.data.decision_note}
                            onChange={(event) => form.setData('decision_note', event.target.value)}
                            placeholder="На эти дни уже согласован отпуск коллеги"
                            aria-invalid={!!form.errors.decision_note}
                        />
                        <InputError message={form.errors.decision_note} />
                        <p className="text-muted-foreground text-[13px]">Её увидит тот, кто подавал заявку.</p>
                    </div>

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={onClose}>
                            Отмена
                        </Button>
                        <Button type="submit" variant="destructive" disabled={form.processing}>
                            Отклонить
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/** The "⋯" at the end of a row: approve, reject, or withdraw your own. */
function RowActions({ leave, onReject }: { leave: LeaveRow; onReject: (leave: LeaveRow) => void }) {
    if (!leave.can.decide && !leave.can.cancel) return null;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="text-muted-foreground size-8" aria-label={`Действия: ${leave.employee.name}`}>
                    <Ellipsis className="size-5!" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-52">
                {leave.can.decide && (
                    <>
                        <DropdownMenuItem onSelect={() => router.post(route('leave.approve', leave.id), {}, { preserveScroll: true })}>
                            <Check />
                            Одобрить
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onSelect={() => onReject(leave)}
                            className="text-[#B42318] focus:text-[#B42318] dark:text-[#F7A19A] [&_svg]:text-current!"
                        >
                            <X />
                            Отклонить…
                        </DropdownMenuItem>
                    </>
                )}

                {leave.can.cancel && (
                    <DropdownMenuItem onSelect={() => router.post(route('leave.cancel', leave.id), {}, { preserveScroll: true })}>
                        <Undo2 />
                        Отозвать
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

/** The columns, each with the filter that narrows it. */
function buildColumns(types: LeaveType[], decides: boolean): ColumnDef[] {
    return [
        ...(decides
            ? [
                  {
                      key: 'employee',
                      label: 'Сотрудник',
                      width: 280,
                      filter: { type: 'text' as const, param: 'employee', placeholder: 'Фамилия или имя' },
                  },
              ]
            : []),
        {
            key: 'type',
            label: 'Вид',
            width: 220,
            filter: { type: 'multi', param: 'type', options: types.map((type) => ({ value: type.id, label: type.name })) },
        },
        { key: 'period', label: 'Период', width: 200, filter: { type: 'dates', from: 'from', to: 'to' } },
        { key: 'days', label: 'Длительность', width: 140 },
        { key: 'status', label: 'Статус', width: 190 },
        { key: 'note', label: 'Комментарий', width: 280 },
    ];
}

const defaultView = (decides: boolean): ViewState => ({ hidden: [], pinned: { left: [decides ? 'employee' : 'type'], right: [] } });

export default function LeaveIndex({ requests, filters, tab, perPage, perPageOptions, balances, types, counts, decides }: Props) {
    const columns = useMemo(() => buildColumns(types, decides), [types, decides]);
    const defaults = useMemo(() => defaultView(decides), [decides]);
    const { view, setView, pin, toggleHidden } = useTableView(
        STORAGE_KEY,
        columns.map((column) => column.key),
        defaults,
    );
    const isHidden = (key: string) => view.hidden.includes(key);

    const [asking, setAsking] = useState(false);
    const [rejecting, setRejecting] = useState<LeaveRow | null>(null);

    /** Everything the list is looking at, as one query string. */
    const visit = (next: { filters?: Partial<Filters>; perPage?: number; tab?: Tab | null }) => {
        const merged = { ...filters, ...next.filters };

        type Value = string | number | (string | number)[] | null;
        const params: Record<string, Value> = {
            ...merged,
            tab: next.tab === undefined ? tab : next.tab,
            per_page: (next.perPage ?? perPage) === perPageOptions[0] ? null : (next.perPage ?? perPage),
        };

        const query = Object.fromEntries(
            Object.entries(params).filter(([, value]) => (Array.isArray(value) ? value.length > 0 : value !== null && value !== '')),
        ) as Record<string, Exclude<Value, null>>;

        router.get(route('leave.index'), query, { preserveState: true, preserveScroll: true, replace: true });
    };

    const tabs: { key: Tab | null; label: string; count: number }[] = [
        { key: null, label: 'Все', count: counts.all },
        { key: 'open', label: 'На согласовании', count: counts.open },
        { key: 'approved', label: 'Одобренные', count: counts.approved },
        { key: 'rejected', label: 'Отклонённые', count: counts.rejected },
    ];

    const activeFilters = countActiveFilters(columns, filters as unknown as Record<string, unknown>, () => true);

    const cell = (column: ColumnDef, leave: LeaveRow) => {
        switch (column.key) {
            case 'employee':
                return (
                    <Link
                        href={route('employees.show', leave.employee.id)}
                        title={`Открыть профиль: ${leave.employee.name}`}
                        className="text-brand-strong flex items-center gap-2 hover:underline dark:text-[#C5E27A]"
                    >
                        {leave.employee.avatar ? (
                            <img src={leave.employee.avatar} alt="" className="size-7 shrink-0 rounded-full object-cover" />
                        ) : (
                            <PersonAvatar name={leave.employee.name} className="size-7 text-[11px]" />
                        )}
                        <span className="truncate">{leave.employee.name}</span>
                    </Link>
                );
            case 'type':
                return <StatusBadge tone={leave.type.tone}>{leave.type.name}</StatusBadge>;
            case 'period':
                return (
                    <span className="tabular-nums">
                        {formatDate(leave.started_on)} – {formatDate(leave.ended_on)}
                    </span>
                );
            case 'days':
                return <span className="tabular-nums">{leave.days} дн.</span>;
            case 'status':
                return (
                    <StatusBadge tone={statusTone[leave.status]} title={leave.decision_note ?? undefined}>
                        {statusLabel[leave.status]}
                    </StatusBadge>
                );
            default:
                return leave.note ?? leave.decision_note ?? <span className="text-muted-foreground">—</span>;
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs} fitViewport>
            <Head title="Отпуска" />

            <div className="flex flex-1 flex-col gap-4 p-3 md:min-h-0 md:px-5 md:py-4">
                <h1 className="text-xl font-semibold tracking-tight">Отпуска и отсутствия</h1>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {balances
                        .filter((balance) => balance.days_per_year !== null)
                        .map((balance) => (
                            <BalanceTile key={balance.id} balance={balance} />
                        ))}
                </div>

                <div className="-mb-2 flex flex-wrap items-center gap-2">
                    <nav aria-label="Состояние заявок" className="flex flex-wrap items-center gap-1 text-sm">
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
                            <Button variant="outline" className="ml-auto h-8 font-normal">
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
                                    disabled={column.key === columns[0].key}
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

                    <Button className="h-8" onClick={() => setAsking(true)}>
                        <Plus />
                        Новая заявка
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    rows={requests.data}
                    rowKey={(leave) => leave.id}
                    renderCell={cell}
                    sort={DEFAULT_SORT}
                    sortable={[]}
                    onSort={() => undefined}
                    filters={filters as unknown as Record<string, unknown>}
                    onFilter={(changes) => visit({ filters: changes as Partial<Filters> })}
                    view={view}
                    onPin={pin}
                    onHide={(key) => toggleHidden(key, true)}
                    lockedKey={columns[0].key}
                    actions={(leave) => <RowActions leave={leave} onReject={setRejecting} />}
                    empty="Заявок пока нет."
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
                            <Pagination paginator={requests} className="min-w-0 flex-1" />
                        </>
                    }
                />
            </div>

            {asking && <RequestDialog types={types} balances={balances} onClose={() => setAsking(false)} />}
            {rejecting && <RejectDialog leave={rejecting} onClose={() => setRejecting(null)} />}
        </AppLayout>
    );
}
