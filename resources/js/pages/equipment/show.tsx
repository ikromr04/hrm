import { ChangeLines } from '@/components/equipment-changes';
import { CategoryChip } from '@/components/equipment-icon';
import { EquipmentMoveDialog, moveLabel, type AskedMove } from '@/components/equipment-move-dialog';
import InputError from '@/components/input-error';
import { PersonAvatar } from '@/components/person-avatar';
import { PhotoInput } from '@/components/photo-input';
import { Photos, type Photo } from '@/components/photo-viewer';
import { SearchableSelect } from '@/components/searchable-select';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/employee';
import {
    eventLabel,
    eventTone,
    formatMoment,
    formatMonth,
    statusLabel,
    statusTone,
    type EquipmentStatus,
    type EventChanges,
    type EventKind,
    type NameLookup,
} from '@/lib/equipment';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowDownToLine,
    Check,
    ChevronLeft,
    ChevronRight,
    Eraser,
    LoaderCircle,
    Pencil,
    Plus,
    Trash2,
    UserPlus,
    type LucideIcon,
} from 'lucide-react';
import { useEffect, useState, type FormEventHandler, type ReactNode } from 'react';

interface Holder {
    id: number;
    name: string;
    avatar: string | null;
    department: string | null;
}

interface Unit {
    id: number;
    name: string;
    equipment_type_id: number;
    type: string | null;
    maker: string | null;
    model: string | null;
    serial_number: string | null;
    inventory_number: string;
    processor: string | null;
    memory: string | null;
    condition: string | null;
    checked_at: string | null;
    next_inventory_at: string | null;
    accessories: string[];
    status: EquipmentStatus;
    issued_at: string | null;
    written_off_at: string | null;
    holder: Holder | null;
    department: string | null;
    /** Set when a whole department holds it rather than one person. */
    holder_department_id: number | null;
}

interface Spell {
    id: number;
    holder: { id: number; name: string } | null;
    department: string | null;
    issued_at: string;
    returned_at: string | null;
    condition_on_return: string | null;
}

interface Repair {
    id: number;
    kind: string;
    started_at: string;
    ended_at: string | null;
    note: string | null;
    /** What was photographed when the work was written down. */
    photos: Photo[];
}

interface JournalEvent {
    id: number;
    photos: Photo[];
    kind: EventKind;
    changes: EventChanges;
    note: string | null;
    at: string | null;
    actor: { id: number; name: string } | null;
}

interface Props {
    unit: Unit;
    assignments: Spell[];
    repairs: Repair[];
    /** Everything that has happened to this unit, newest first. */
    events: JournalEvent[];
    /** Names for the ids the entries kept: field => { id: name }. */
    names: NameLookup;
    holders: { id: number; name: string }[];
    /** The categories the «Характеристики» form offers; empty for a viewer. */
    types: { id: number; name: string }[];
    departments: { id: number; name: string }[];
    neighbours: { prev: Neighbour; next: Neighbour };
    canEdit: boolean;
}

/** The unit before or after this one in the list; null at either end. */
type Neighbour = { id: number; name: string; inventory_number: string } | null;

const tabs = [
    { key: 'overview', title: 'Обзор' },
    { key: 'history', title: 'История передач' },
    { key: 'service', title: 'Обслуживание' },
    { key: 'journal', title: 'Журнал' },
] as const;

type TabKey = (typeof tabs)[number]['key'];

/** The open section rides in the URL hash, so a tab can be linked and survives a reload. */
function useTab(): [TabKey, (key: TabKey) => void] {
    const fromHash = () => {
        const key = window.location.hash.replace('#', '') as TabKey;

        return tabs.some((tab) => tab.key === key) ? key : 'overview';
    };
    const [tab, setTab] = useState<TabKey>(fromHash);

    // Back and forward move between tabs, like between pages.
    useEffect(() => {
        const onHashChange = () => setTab(fromHash());
        window.addEventListener('hashchange', onHashChange);

        return () => window.removeEventListener('hashchange', onHashChange);
    });

    return [
        tab,
        (key: TabKey) => {
            setTab(key);
            window.history.replaceState(null, '', key === 'overview' ? window.location.pathname : `#${key}`);
        },
    ];
}

/* --------------------------------------------------------------- building blocks */

function Section({ title, children, action }: { title: string; children: ReactNode; action?: ReactNode }) {
    return (
        <Card className="flex flex-col gap-4 rounded-xl px-6 py-5">
            <div className="bg-muted/60 -mx-6 -mt-5 flex min-h-11 items-center justify-between gap-3 rounded-t-xl border-b px-6 py-2">
                <h2 className="text-base font-semibold">{title}</h2>
                {action}
            </div>
            {children}
        </Card>
    );
}

/** Fields in newspaper columns: they read top to bottom, up to three across. */
function Fields({ children, columns }: { children: ReactNode; columns?: 1 }) {
    return <dl className={cn('gap-x-6', columns === 1 ? 'columns-1' : 'columns-1 sm:columns-2 lg:columns-3')}>{children}</dl>;
}

function Field({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="mb-4 flex min-w-0 break-inside-avoid flex-col gap-1">
            <dt className="text-muted-foreground text-[13px]">{label}</dt>
            <dd className="text-sm font-medium break-words">{children ?? <span className="text-muted-foreground font-normal">—</span>}</dd>
        </div>
    );
}

const dash = <span className="text-muted-foreground">—</span>;

/** Errors come back as "photos.0"; a field shows its own, whichever it is. */
const at = (errors: Record<string, string | undefined>, key: string) =>
    errors[key] ?? Object.entries(errors).find(([name]) => name.startsWith(`${key}.`))?.[1];

/** The pencil in a block's header strip, as on the employee's profile. */
function EditButton({ what, onClick }: { what: string; onClick: () => void }) {
    return (
        <Button variant="ghost" size="icon" className="text-muted-foreground -mr-2 size-7" aria-label={`Редактировать ${what}`} onClick={onClick}>
            <Pencil className="size-4" />
        </Button>
    );
}

/**
 * Walking the fleet without going back to the list. The open tab travels with
 * the link, so comparing two units keeps you on the same section, and the
 * arrow keys do the same as the buttons.
 */
function Neighbours({ prev, next, tab }: { prev: Neighbour; next: Neighbour; tab: TabKey }) {
    const href = (to: Neighbour) => route('equipment.show', to!.id) + (tab === 'overview' ? '' : `#${tab}`);

    useEffect(() => {
        const onKeyDown = (event: KeyboardEvent) => {
            if (event.ctrlKey || event.altKey || event.metaKey || event.shiftKey) return;
            const target = event.target as HTMLElement;
            if (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)) return;
            const to = event.key === 'ArrowLeft' ? prev : event.key === 'ArrowRight' ? next : null;
            if (to) router.visit(href(to));
        };
        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    });

    const arrow = (to: Neighbour, label: string, Icon: typeof ChevronLeft) => {
        // The arrow leads the label going back and trails it going forward.
        const back = Icon === ChevronLeft;

        return (
            <Button variant="outline" disabled={!to} aria-label={to ? `${label}: ${to.name}` : label} title={to?.name} asChild={!!to}>
                {to ? (
                    <Link href={href(to)} prefetch>
                        {back && <Icon />}
                        {label}
                        {!back && <Icon />}
                    </Link>
                ) : (
                    <>
                        {back && <Icon />}
                        {label}
                        {!back && <Icon />}
                    </>
                )}
            </Button>
        );
    };

    return (
        <div className="flex gap-1">
            {arrow(prev, 'Предыдущее', ChevronLeft)}
            {arrow(next, 'Следующее', ChevronRight)}
        </div>
    );
}

/**
 * Where a unit can go next, as one segmented control at the foot of the
 * sidebar — the same shape the employee's profile gives Перевести, Уволить
 * and Удалить.
 */
function MoveGroup({ moves, onPick }: { moves: { kind: AskedMove; icon: LucideIcon; danger?: boolean }[]; onPick: (kind: AskedMove) => void }) {
    return (
        <div className="bg-background flex w-full items-stretch overflow-hidden rounded-md border">
            {moves.map(({ kind, icon: Icon, danger }, index) => (
                <button
                    key={kind}
                    type="button"
                    onClick={() => onPick(kind)}
                    title={moveLabel[kind]}
                    className={cn(
                        'hover:bg-accent focus-visible:ring-ring flex min-w-0 flex-1 items-center justify-center gap-1.5 px-2 py-2 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:outline-hidden',
                        index > 0 && 'border-l',
                        danger && 'text-[#B42318] dark:text-[#F7A19A]',
                    )}
                >
                    <Icon className="size-4 shrink-0" />
                    <span className="truncate">{moveLabel[kind]}</span>
                </button>
            ))}
        </div>
    );
}

/** A plain table for the history and service tabs. */
function Table({ head, children }: { head: string[]; children: ReactNode }) {
    return (
        <div className="scroll-soft -mx-6 overflow-x-auto">
            <table className="w-full min-w-[640px] border-collapse text-sm">
                <thead>
                    <tr className="text-muted-foreground text-left text-[13px]">
                        {head.map((title) => (
                            <th key={title} scope="col" className="px-6 py-2 font-medium first:pl-6">
                                {title}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>{children}</tbody>
            </table>
        </div>
    );
}

/* --------------------------------------------------------------------- dialogs */

/** The "Характеристики" block in a form: what the unit is and what it cost. */
function SpecsDialog({ unit, types, onClose }: { unit: Unit; types: { id: number; name: string }[]; onClose: () => void }) {
    const form = useForm({
        equipment_type_id: String(unit.equipment_type_id),
        name: unit.name,
        maker: unit.maker ?? '',
        model: unit.model ?? '',
        serial_number: unit.serial_number ?? '',
        inventory_number: unit.inventory_number,
        processor: unit.processor ?? '',
        memory: unit.memory ?? '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.put(route('equipment.specs', unit.id), { preserveScroll: true, onSuccess: onClose });
    };

    /** Every field here is a label over an input; only the value differs. */
    const text = (key: 'name' | 'maker' | 'model' | 'serial_number' | 'inventory_number' | 'processor' | 'memory', label: string, hint: string) => (
        <div className="grid content-start gap-2">
            <Label htmlFor={`specs-${key}`}>{label}</Label>
            <Input
                id={`specs-${key}`}
                value={form.data[key]}
                onChange={(event) => form.setData(key, event.target.value)}
                placeholder={hint}
                aria-invalid={!!form.errors[key]}
            />
            <InputError message={form.errors[key]} />
        </div>
    );

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="scroll-soft max-h-[85vh] overflow-y-auto sm:max-w-lg">
                {/* noValidate: the server's rules are the real ones. */}
                <form onSubmit={submit} noValidate className="flex flex-col gap-5">
                    <DialogHeader>
                        <DialogTitle>Характеристики</DialogTitle>
                        <DialogDescription>Что это за единица и во сколько она обошлась.</DialogDescription>
                    </DialogHeader>

                    {text('name', 'Наименование', 'Ноутбук Dell Latitude 5440')}

                    <div className="grid content-start gap-2">
                        <Label htmlFor="specs-type">Категория</Label>
                        <SearchableSelect
                            id="specs-type"
                            value={form.data.equipment_type_id}
                            onChange={(value) => form.setData('equipment_type_id', value)}
                            options={types.map((type) => ({ value: String(type.id), label: type.name }))}
                            placeholder="Выберите категорию"
                            searchPlaceholder="Поиск категории"
                            empty="Категория не найдена"
                            invalid={!!form.errors.equipment_type_id}
                        />
                        <InputError message={form.errors.equipment_type_id} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        {text('maker', 'Производитель', 'Dell')}
                        {text('model', 'Модель', 'Latitude 5440')}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        {text('serial_number', 'Серийный номер', '7K2L9P3')}
                        {text('inventory_number', 'Инвентарный номер', 'EV-0421')}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        {text('processor', 'Процессор', 'Intel Core i5-1335U')}
                        {text('memory', 'Память / диск', '16 ГБ / SSD 512 ГБ')}
                    </div>

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={onClose}>
                            Отмена
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <LoaderCircle className="animate-spin" />}
                            Сохранить
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/** The "Состояние" block: what shape it is in, checked when, due when. */
function StateDialog({ unit, onClose }: { unit: Unit; onClose: () => void }) {
    const today = new Date().toISOString().slice(0, 10);
    // A check happens now and the next one is due a year from now, so the form
    // opens on those rather than on whatever the last check left behind.
    const inAYear = new Date();
    inAYear.setFullYear(inAYear.getFullYear() + 1);

    const [photos, setPhotos] = useState<File[]>([]);

    const form = useForm<{ condition: string; checked_at: string; next_inventory_at: string; photos: File[] }>({
        condition: unit.condition ?? '',
        checked_at: today,
        next_inventory_at: inAYear.toISOString().slice(0, 10),
        photos: [],
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, photos, _method: 'put' }));
        // Multipart, so the upload is a POST that says it is a PUT.
        form.post(route('equipment.state', unit.id), { preserveScroll: true, forceFormData: true, onSuccess: onClose });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-md">
                {/* noValidate: the server's rules are the real ones. */}
                <form onSubmit={submit} noValidate className="flex flex-col gap-5">
                    <DialogHeader>
                        <DialogTitle>Проверка состояния</DialogTitle>
                        <DialogDescription>Запись о проверке и её снимки останутся в журнале.</DialogDescription>
                    </DialogHeader>

                    <div className="grid content-start gap-2">
                        <Label htmlFor="state-condition">Текущее состояние</Label>
                        <Input
                            id="state-condition"
                            value={form.data.condition}
                            onChange={(event) => form.setData('condition', event.target.value)}
                            placeholder="Рабочее, без повреждений"
                            aria-invalid={!!form.errors.condition}
                        />
                        <InputError message={form.errors.condition} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid content-start gap-2">
                            <Label htmlFor="state-checked">Последняя проверка</Label>
                            <Input
                                id="state-checked"
                                type="date"
                                max={today}
                                value={form.data.checked_at}
                                onChange={(event) => form.setData('checked_at', event.target.value)}
                                aria-invalid={!!form.errors.checked_at}
                            />
                            <InputError message={form.errors.checked_at} />
                        </div>

                        <div className="grid content-start gap-2">
                            <Label htmlFor="state-next">След. инвентаризация</Label>
                            <Input
                                id="state-next"
                                type="date"
                                value={form.data.next_inventory_at}
                                onChange={(event) => form.setData('next_inventory_at', event.target.value)}
                                aria-invalid={!!form.errors.next_inventory_at}
                            />
                            <InputError message={form.errors.next_inventory_at} />
                            <p className="text-muted-foreground text-[13px]">На карточке покажем месяц.</p>
                        </div>
                    </div>

                    <PhotoInput
                        photos={photos}
                        onChange={setPhotos}
                        error={at(form.errors, 'photos')}
                        hint="Снимки прошлых проверок остаются в журнале — новые их не заменяют."
                    />

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={onClose}>
                            Отмена
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <LoaderCircle className="animate-spin" />}
                            Сохранить
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/**
 * Correcting the handover the unit is on — a wrong date, a missing act, the
 * wrong colleague. It is not a move: the unit stays issued, and the open spell
 * in its history is corrected along with the card.
 */
function HandoverDialog({
    unit,
    holders,
    departments,
    onClose,
}: {
    unit: Unit;
    holders: { id: number; name: string }[];
    departments: { id: number; name: string }[];
    onClose: () => void;
}) {
    const today = new Date().toISOString().slice(0, 10);
    const form = useForm({
        holder_user_id: unit.holder ? String(unit.holder.id) : '',
        holder_department_id: unit.holder_department_id ? String(unit.holder_department_id) : '',
        issued_at: unit.issued_at ?? today,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.put(route('equipment.handover', unit.id), { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-md">
                {/* noValidate: the server's rules are the real ones. */}
                <form onSubmit={submit} noValidate className="flex flex-col gap-5">
                    <DialogHeader>
                        <DialogTitle>Выдача</DialogTitle>
                        <DialogDescription>Единица остаётся выданной — правится только запись о ней.</DialogDescription>
                    </DialogHeader>

                    <div className="grid content-start gap-2">
                        <Label htmlFor="handover-holder">Сотрудник</Label>
                        <SearchableSelect
                            id="handover-holder"
                            value={form.data.holder_user_id}
                            // One holder at a time: picking a person lets the department go.
                            onChange={(value) => form.setData({ ...form.data, holder_user_id: value, holder_department_id: '' })}
                            options={holders.map((holder) => ({ value: String(holder.id), label: holder.name }))}
                            placeholder="Не выбран"
                            searchPlaceholder="Поиск по фамилии или имени"
                            empty="Сотрудник не найден"
                            invalid={!!form.errors.holder_user_id}
                        />
                        <InputError message={form.errors.holder_user_id} />
                    </div>

                    <div className="grid content-start gap-2">
                        <Label htmlFor="handover-department">Либо отдел</Label>
                        <SearchableSelect
                            id="handover-department"
                            value={form.data.holder_department_id}
                            onChange={(value) => form.setData({ ...form.data, holder_department_id: value, holder_user_id: '' })}
                            options={departments.map((department) => ({ value: String(department.id), label: department.name }))}
                            placeholder="Не выбран"
                            searchPlaceholder="Поиск отдела"
                            empty="Отдел не найден"
                            invalid={!!form.errors.holder_department_id}
                        />
                        <InputError message={form.errors.holder_department_id} />
                        <p className="text-muted-foreground text-[13px]">Техника числится либо за человеком, либо за отделом.</p>
                    </div>

                    <div className="grid content-start gap-2">
                        <Label htmlFor="handover-issued">Дата выдачи</Label>
                        <Input
                            id="handover-issued"
                            type="date"
                            max={today}
                            value={form.data.issued_at}
                            onChange={(event) => form.setData('issued_at', event.target.value)}
                            aria-invalid={!!form.errors.issued_at}
                        />
                        <InputError message={form.errors.issued_at} />
                    </div>

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={onClose}>
                            Отмена
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <LoaderCircle className="animate-spin" />}
                            Сохранить
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/** What comes with the unit, one line each; an empty line drops out on save. */
function AccessoriesDialog({ unit, onClose }: { unit: Unit; onClose: () => void }) {
    const [items, setItems] = useState<string[]>(unit.accessories.length > 0 ? unit.accessories : ['']);
    const form = useForm<{ accessories: string[] }>({ accessories: unit.accessories });

    const change = (index: number, value: string) => setItems(items.map((item, at) => (at === index ? value : item)));

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.transform(() => ({ accessories: items.map((item) => item.trim()).filter(Boolean) }));
        form.put(route('equipment.accessories', unit.id), { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-md">
                {/* noValidate: the server's rules are the real ones. */}
                <form onSubmit={submit} noValidate className="flex flex-col gap-5">
                    <DialogHeader>
                        <DialogTitle>Комплектация</DialogTitle>
                        <DialogDescription>Что выдаётся вместе с техникой.</DialogDescription>
                    </DialogHeader>

                    <div className="flex flex-col gap-2">
                        {items.map((item, index) => (
                            <div key={index} className="flex items-center gap-2">
                                <Input
                                    value={item}
                                    onChange={(event) => change(index, event.target.value)}
                                    placeholder="Блок питания 65 Вт"
                                    aria-label={`Позиция ${index + 1}`}
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="text-muted-foreground size-9 shrink-0"
                                    aria-label={`Убрать позицию ${index + 1}`}
                                    onClick={() => setItems(items.length === 1 ? [''] : items.filter((_, at) => at !== index))}
                                >
                                    <Trash2 className="size-4" />
                                </Button>
                            </div>
                        ))}
                        <InputError message={form.errors.accessories} />

                        <Button type="button" variant="outline" size="sm" className="self-start" onClick={() => setItems([...items, ''])}>
                            <Plus />
                            Добавить позицию
                        </Button>
                    </div>

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={onClose}>
                            Отмена
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <LoaderCircle className="animate-spin" />}
                            Сохранить
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/**
 * A piece of work done on a unit: a cleaning, a part replaced, a trip to a
 * shop. It is a note in the history and moves nothing — an empty end date
 * only means the work is not finished yet.
 */
function RepairDialog({ unit, repair, finishing, onClose }: { unit: Unit; repair?: Repair; finishing?: boolean; onClose: () => void }) {
    const today = new Date().toISOString().slice(0, 10);

    const [photos, setPhotos] = useState<File[]>([]);

    const form = useForm({
        kind: repair?.kind ?? '',
        started_at: repair?.started_at ?? today,
        // Only "Завершить" fills the date in, because that is what it is for.
        // A correction opens on what the record says, empty included.
        ended_at: finishing ? today : (repair?.ended_at ?? ''),
        note: repair?.note ?? '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        // Multipart, so a correction is a POST that says it is a PUT.
        form.transform((data) => ({ ...data, photos, ...(repair ? { _method: 'put' } : {}) }));

        const url = repair ? route('equipment.repairs.update', [unit.id, repair.id]) : route('equipment.repairs.store', unit.id);

        form.post(url, { preserveScroll: true, forceFormData: true, onSuccess: onClose });
    };

    const title = finishing ? 'Завершить обслуживание' : repair ? 'Изменить запись' : 'Обслуживание';

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className={finishing ? 'sm:max-w-md' : 'sm:max-w-lg'}>
                {/* noValidate: the server's rules are the real ones. */}
                <form onSubmit={submit} noValidate className="flex flex-col gap-5">
                    <DialogHeader>
                        <DialogTitle>{title}</DialogTitle>
                        <DialogDescription>
                            {finishing && repair
                                ? `${repair.kind} · с ${formatDate(repair.started_at)}`
                                : `${unit.name} · инв. № ${unit.inventory_number}`}
                        </DialogDescription>
                    </DialogHeader>

                    {/* Finishing asks one thing, so it shows one field. */}
                    {finishing ? (
                        <div className="grid content-start gap-2">
                            <Label htmlFor="repair-ended">Дата окончания</Label>
                            <Input
                                id="repair-ended"
                                type="date"
                                min={form.data.started_at || undefined}
                                max={today}
                                value={form.data.ended_at}
                                onChange={(event) => form.setData('ended_at', event.target.value)}
                                aria-invalid={!!form.errors.ended_at}
                            />
                            <InputError message={form.errors.ended_at} />
                            <p className="text-muted-foreground text-[13px]">Единица уйдёт с вкладки «На обслуживании».</p>
                        </div>
                    ) : (
                        <>
                            <div className="grid content-start gap-2">
                                <Label htmlFor="repair-kind">Тип работ</Label>
                                <Input
                                    id="repair-kind"
                                    value={form.data.kind}
                                    onChange={(event) => form.setData('kind', event.target.value)}
                                    placeholder="Чистка и замена термопасты"
                                    aria-invalid={!!form.errors.kind}
                                />
                                <InputError message={form.errors.kind} />
                            </div>

                            <div className="grid content-start gap-2">
                                <Label htmlFor="repair-note">Комментарий</Label>
                                <Input
                                    id="repair-note"
                                    value={form.data.note}
                                    onChange={(event) => form.setData('note', event.target.value)}
                                    placeholder="Плановое ТО"
                                    aria-invalid={!!form.errors.note}
                                />
                                <InputError message={form.errors.note} />
                            </div>
                            {/*
                             * Side by side and level with each other. A cell of a grid
                             * stretches to its row, and a grid inside it would spread
                             * its own rows over that height — which is what pushed the
                             * left field down beside the taller right one. `content-start`
                             * keeps each field packed at the top instead.
                             */}
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid content-start gap-2">
                                    <Label htmlFor="repair-started">Дата начала</Label>
                                    <Input
                                        id="repair-started"
                                        type="date"
                                        max={today}
                                        value={form.data.started_at}
                                        onChange={(event) => form.setData('started_at', event.target.value)}
                                        aria-invalid={!!form.errors.started_at}
                                    />
                                    <InputError message={form.errors.started_at} />
                                </div>

                                <div className="grid content-start gap-2">
                                    <Label htmlFor="repair-ended">Дата окончания</Label>
                                    <Input
                                        id="repair-ended"
                                        type="date"
                                        min={form.data.started_at || undefined}
                                        value={form.data.ended_at}
                                        onChange={(event) => form.setData('ended_at', event.target.value)}
                                        aria-invalid={!!form.errors.ended_at}
                                    />
                                    <InputError message={form.errors.ended_at} />
                                    <p className="text-muted-foreground text-[13px]">Пусто — работы ещё идут, и единица числится на обслуживании.</p>
                                </div>
                            </div>

                            <PhotoInput
                                photos={photos}
                                onChange={setPhotos}
                                error={at(form.errors, 'photos')}
                                hint="Останутся на записи и в журнале. Прошлые снимки не заменяются."
                            />
                        </>
                    )}

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={onClose}>
                            Отмена
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <LoaderCircle className="animate-spin" />}
                            Сохранить
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
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
                    Вместе с единицей исчезнут её история передач, обслуживание и журнал. Отменить это нельзя. Если техника просто отслужила своё — её
                    нужно <span className="font-medium">списать</span>, а не удалять.
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
                            router.delete(route('equipment.destroy', unit.id));
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

/**
 * A line of the unit's service history is not struck out by a stray click on
 * the bin, so it is asked about first.
 */
function RepairDeleteDialog({ unit, repair, onClose }: { unit: Unit; repair: Repair; onClose: () => void }) {
    const [busy, setBusy] = useState(false);

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Удалить запись об обслуживании?</DialogTitle>
                    <DialogDescription>
                        {repair.kind} ·{' '}
                        {repair.ended_at ? `${formatDate(repair.started_at)} – ${formatDate(repair.ended_at)}` : `с ${formatDate(repair.started_at)}`}
                    </DialogDescription>
                </DialogHeader>

                <p className="text-sm">Запись исчезнет из вкладки «Обслуживание». В журнале останется отметка о том, что её удалили.</p>

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
                            router.delete(route('equipment.repairs.destroy', [unit.id, repair.id]), {
                                preserveScroll: true,
                                onFinish: onClose,
                            });
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

/* ------------------------------------------------------------------------ page */

export default function EquipmentShow({ unit, assignments, repairs, events, names, holders, types, departments, neighbours, canEdit }: Props) {
    const [tab, setTab] = useTab();
    const [asking, setAsking] = useState<AskedMove | null>(null);
    const [repairing, setRepairing] = useState(false);
    const [removing, setRemoving] = useState<Repair | null>(null);
    const [correcting, setCorrecting] = useState<Repair | null>(null);
    const [closing, setClosing] = useState<Repair | null>(null);
    const [deleting, setDeleting] = useState(false);
    /** Which block of the card is open in a form. */
    const [editing, setEditing] = useState<'specs' | 'accessories' | 'state' | 'handover' | null>(null);

    // The newest entry that came with photographs is the last look anyone had.
    const lastPhotos = events.find((event) => event.photos.length > 0)?.photos ?? [];

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Оборудование', href: '/equipment' },
        { title: unit.name, href: route('equipment.show', unit.id) },
    ];

    /** What a unit can be moved to next, given where it is now. */
    const moves: { kind: AskedMove; icon: LucideIcon; danger?: boolean }[] =
        unit.status === 'written_off'
            ? []
            : [
                  ...(unit.status === 'issued' ? [{ kind: 'take' as const, icon: ArrowDownToLine }] : [{ kind: 'issue' as const, icon: UserPlus }]),
                  { kind: 'write-off' as const, icon: Trash2, danger: true },
              ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={unit.name} />

            <div className="flex flex-1 flex-col gap-5 p-3 md:px-5 md:py-4">
                {/* Aligned along the bottom, so the title, the actions and the arrows sit on one line. */}
                <div className="flex flex-wrap items-end gap-5">
                    <CategoryChip type={unit.type} size={72} iconSize={32} />

                    <div className="flex min-w-0 flex-1 flex-col gap-2">
                        <div className="flex flex-wrap items-center gap-3">
                            <h1 className="text-xl font-semibold tracking-tight">{unit.name}</h1>
                            <StatusBadge tone={statusTone[unit.status]}>{statusLabel[unit.status]}</StatusBadge>
                        </div>

                        <p className="text-muted-foreground flex flex-wrap items-center gap-x-2 text-sm">
                            <span>Инв. № {unit.inventory_number}</span>
                            {unit.serial_number && (
                                <>
                                    <span aria-hidden="true">·</span>
                                    <span>S/N {unit.serial_number}</span>
                                </>
                            )}
                            {unit.type && (
                                <>
                                    <span aria-hidden="true">·</span>
                                    {/* The category leads back to the list, narrowed to it. */}
                                    <Link
                                        href={route('equipment.index', { type: [unit.equipment_type_id] })}
                                        title={`Вся категория: ${unit.type}`}
                                        className="text-brand-strong hover:underline dark:text-[#C5E27A]"
                                    >
                                        {unit.type}
                                    </Link>
                                </>
                            )}
                        </p>
                    </div>

                    <Neighbours prev={neighbours.prev} next={neighbours.next} tab={tab} />
                </div>

                <nav aria-label="Разделы" className="scroll-soft flex gap-6 overflow-x-auto">
                    {tabs.map((item) => (
                        <button
                            key={item.key}
                            type="button"
                            onClick={() => setTab(item.key)}
                            aria-current={item.key === tab ? 'page' : undefined}
                            className={cn(
                                'shrink-0 border-b-2 px-1 pb-2.5 text-sm transition-colors',
                                item.key === tab
                                    ? 'border-brand text-foreground font-semibold'
                                    : 'text-muted-foreground hover:text-foreground border-transparent font-medium',
                            )}
                        >
                            {item.title}
                        </button>
                    ))}
                </nav>

                {tab === 'overview' && (
                    <div className="grid gap-4 lg:grid-cols-[1fr_26.4rem]">
                        <div className="flex flex-col gap-4">
                            <Section
                                title="Характеристики"
                                action={canEdit && <EditButton what="характеристики" onClick={() => setEditing('specs')} />}
                            >
                                <Fields>
                                    <Field label="Категория">
                                        {unit.type && (
                                            <Link
                                                href={route('equipment.index', { type: [unit.equipment_type_id] })}
                                                title={`Вся категория: ${unit.type}`}
                                                className="text-brand-strong hover:underline dark:text-[#C5E27A]"
                                            >
                                                {unit.type}
                                            </Link>
                                        )}
                                    </Field>
                                    <Field label="Производитель">{unit.maker}</Field>
                                    <Field label="Модель">{unit.model}</Field>
                                    <Field label="Серийный номер">{unit.serial_number}</Field>
                                    <Field label="Инвентарный номер">{unit.inventory_number}</Field>
                                    <Field label="Процессор">{unit.processor}</Field>
                                    <Field label="Память / диск">{unit.memory}</Field>
                                </Fields>
                            </Section>

                            <Section
                                title="Комплектация"
                                action={canEdit && <EditButton what="комплектацию" onClick={() => setEditing('accessories')} />}
                            >
                                {unit.accessories.length === 0 ? (
                                    <p className="text-muted-foreground text-sm">Ничего не записано</p>
                                ) : (
                                    <div className="flex flex-wrap gap-2">
                                        {unit.accessories.map((item) => (
                                            <StatusBadge key={item} tone="neutral">
                                                {item}
                                            </StatusBadge>
                                        ))}
                                    </div>
                                )}
                            </Section>
                        </div>

                        <div className="flex flex-col gap-4">
                            <Section
                                title={unit.holder ? 'Сейчас у сотрудника' : 'Где сейчас'}
                                action={canEdit && unit.status === 'issued' && <EditButton what="выдачу" onClick={() => setEditing('handover')} />}
                            >
                                {unit.holder ? (
                                    <>
                                        <div className="flex items-center gap-3">
                                            {unit.holder.avatar ? (
                                                <img src={unit.holder.avatar} alt="" className="size-10 shrink-0 rounded-full object-cover" />
                                            ) : (
                                                <PersonAvatar name={unit.holder.name} className="size-10 text-[13px]" />
                                            )}
                                            <div className="flex min-w-0 flex-col">
                                                <Link
                                                    href={route('employees.show', unit.holder.id)}
                                                    className="truncate text-sm font-semibold hover:underline"
                                                >
                                                    {unit.holder.name}
                                                </Link>
                                                {unit.holder.department && (
                                                    <span className="text-muted-foreground truncate text-[13px]">{unit.holder.department}</span>
                                                )}
                                            </div>
                                        </div>

                                        <Fields columns={1}>
                                            <Field label="Выдано">{formatDate(unit.issued_at)}</Field>
                                        </Fields>
                                    </>
                                ) : (
                                    <Fields columns={1}>
                                        <Field label="Статус">{statusLabel[unit.status]}</Field>
                                        {unit.department && <Field label="Отдел">{unit.department}</Field>}
                                        {unit.status === 'written_off' && <Field label="Списано">{formatDate(unit.written_off_at)}</Field>}
                                    </Fields>
                                )}
                            </Section>

                            <Section title="Состояние" action={canEdit && <EditButton what="состояние" onClick={() => setEditing('state')} />}>
                                <Fields columns={1}>
                                    <Field label="Текущее состояние">{unit.condition}</Field>
                                    <Field label="Последняя проверка">{formatDate(unit.checked_at)}</Field>
                                    <Field label="След. инвентаризация">{formatMonth(unit.next_inventory_at)}</Field>
                                </Fields>

                                <Photos photos={lastPhotos} />
                            </Section>

                            {canEdit && moves.length > 0 && <MoveGroup moves={moves} onPick={setAsking} />}

                            {/* Written off and nowhere left to go: only striking it off remains. */}
                            {canEdit && unit.status === 'written_off' && (
                                <Button
                                    variant="outline"
                                    className="border-[#F5C9C4] text-[#B42318] hover:text-[#B42318] dark:text-[#F7A19A]"
                                    onClick={() => setDeleting(true)}
                                >
                                    <Eraser />
                                    Удалить запись
                                </Button>
                            )}
                        </div>
                    </div>
                )}

                {tab === 'history' && (
                    <Section
                        title="История передач"
                        action={
                            canEdit &&
                            unit.status !== 'written_off' &&
                            unit.status !== 'issued' && (
                                <Button variant="outline" size="sm" onClick={() => setAsking('issue')}>
                                    <UserPlus />
                                    Выдать
                                </Button>
                            )
                        }
                    >
                        {assignments.length === 0 ? (
                            <p className="text-muted-foreground text-sm">Передач пока не было</p>
                        ) : (
                            <Table head={['Сотрудник', 'Выдано', 'Возвращено', 'Состояние при возврате']}>
                                {assignments.map((spell) => (
                                    <tr key={spell.id} className="border-t">
                                        <td className="px-6 py-2.5">
                                            {spell.holder ? (
                                                <Link href={route('employees.show', spell.holder.id)} className="font-medium hover:underline">
                                                    {spell.holder.name}
                                                </Link>
                                            ) : (
                                                <span className="font-medium">{spell.department ?? 'Склад'}</span>
                                            )}
                                        </td>
                                        <td className="px-6 py-2.5 tabular-nums">{formatDate(spell.issued_at)}</td>
                                        <td className="px-6 py-2.5 tabular-nums">{formatDate(spell.returned_at) ?? dash}</td>
                                        <td className="px-6 py-2.5">
                                            {spell.returned_at === null ? (
                                                <StatusBadge tone="success">{spell.holder ? 'В работе' : 'Здесь сейчас'}</StatusBadge>
                                            ) : (
                                                (spell.condition_on_return ?? dash)
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </Table>
                        )}
                    </Section>
                )}

                {tab === 'service' && (
                    <Section
                        title="Обслуживание"
                        action={
                            canEdit &&
                            unit.status !== 'written_off' && (
                                <Button variant="outline" size="sm" onClick={() => setRepairing(true)}>
                                    <Plus />
                                    Добавить запись
                                </Button>
                            )
                        }
                    >
                        {repairs.length === 0 ? (
                            <p className="text-muted-foreground text-sm">Записей об обслуживании нет</p>
                        ) : (
                            <Table head={['Тип', 'Период', 'Комментарий', 'Фото', '']}>
                                {repairs.map((repair) => (
                                    <tr key={repair.id} className="border-t">
                                        <td className="px-6 py-2.5 font-medium">{repair.kind}</td>
                                        <td className="px-6 py-2.5 tabular-nums">
                                            {repair.ended_at
                                                ? `${formatDate(repair.started_at)} – ${formatDate(repair.ended_at)}`
                                                : `с ${formatDate(repair.started_at)}`}
                                        </td>
                                        <td className="px-6 py-2.5">{repair.note ?? dash}</td>
                                        <td className="px-6 py-2.5">{repair.photos.length > 0 ? <Photos photos={repair.photos} /> : dash}</td>
                                        <td className="py-2.5 pr-6 text-right">
                                            {canEdit && (
                                                <div className="flex items-center justify-end gap-1">
                                                    {/* Until the work has an end date the unit counts as being looked after. */}
                                                    {repair.ended_at === null && (
                                                        <Button variant="outline" size="sm" className="h-8" onClick={() => setClosing(repair)}>
                                                            <Check />
                                                            Завершить
                                                        </Button>
                                                    )}

                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="text-muted-foreground size-8"
                                                        aria-label={`Изменить запись: ${repair.kind}`}
                                                        onClick={() => setCorrecting(repair)}
                                                    >
                                                        <Pencil className="size-4" />
                                                    </Button>

                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="text-muted-foreground size-8"
                                                        aria-label={`Удалить запись: ${repair.kind}`}
                                                        onClick={() => setRemoving(repair)}
                                                    >
                                                        <Trash2 />
                                                    </Button>
                                                </div>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </Table>
                        )}
                    </Section>
                )}

                {tab === 'journal' && (
                    <Section title="Журнал">
                        {events.length === 0 ? (
                            <p className="text-muted-foreground text-sm">Пока ничего не происходило</p>
                        ) : (
                            <ol className="flex flex-col">
                                {events.map((event) => (
                                    <li
                                        key={event.id}
                                        className="flex flex-wrap items-start gap-x-4 gap-y-1 border-t py-3 first:border-t-0 first:pt-0"
                                    >
                                        <span className="text-muted-foreground w-28 shrink-0 text-[13px] tabular-nums">{formatMoment(event.at)}</span>

                                        <StatusBadge tone={eventTone[event.kind]}>{eventLabel[event.kind]}</StatusBadge>

                                        <ChangeLines changes={event.changes} names={names} note={event.note} className="min-w-0 flex-1" />

                                        <span className="text-muted-foreground shrink-0 text-[13px]">{event.actor?.name ?? 'Система'}</span>

                                        {event.photos.length > 0 && <Photos photos={event.photos} className="basis-full pl-44" />}
                                    </li>
                                ))}
                            </ol>
                        )}
                    </Section>
                )}
            </div>

            {asking && <EquipmentMoveDialog unit={unit} kind={asking} holders={holders} onClose={() => setAsking(null)} />}
            {repairing && <RepairDialog unit={unit} onClose={() => setRepairing(false)} />}
            {correcting && <RepairDialog unit={unit} repair={correcting} onClose={() => setCorrecting(null)} />}
            {closing && <RepairDialog unit={unit} repair={closing} finishing onClose={() => setClosing(null)} />}
            {removing && <RepairDeleteDialog unit={unit} repair={removing} onClose={() => setRemoving(null)} />}
            {deleting && <DeleteDialog unit={unit} onClose={() => setDeleting(false)} />}
            {editing === 'specs' && <SpecsDialog unit={unit} types={types} onClose={() => setEditing(null)} />}
            {editing === 'accessories' && <AccessoriesDialog unit={unit} onClose={() => setEditing(null)} />}
            {editing === 'state' && <StateDialog unit={unit} onClose={() => setEditing(null)} />}
            {editing === 'handover' && <HandoverDialog unit={unit} holders={holders} departments={departments} onClose={() => setEditing(null)} />}
        </AppLayout>
    );
}
