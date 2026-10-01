import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Collapsible, CollapsibleContent } from '@/components/ui/collapsible';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import { ChevronDown, Info } from 'lucide-react';
import { useState } from 'react';

/** One line of an employee card, with the right to read it and the right to change it. */
export interface CardField {
    key: string;
    title: string;
    /** Never closed to anybody: the surname on a card, and the name. */
    always?: boolean;
    /** "employees.field.phone" */
    permission: string;
    /** "employees.edit.phone" */
    editPermission: string;
}

/** A block of the card. An expandable one is chosen line by line. */
export interface CardFieldGroup {
    key: string;
    title: string;
    /** Half a passport is no use, so a passport is one answer; contacts are not. */
    expandable: boolean;
    fields: CardField[];
}

export type CardFieldsMode = 'view' | 'edit';

/**
 * The right behind a box, or null where there is none: a line everybody reads has
 * nothing to tick in «Просмотр», though changing it is a right like any other.
 */
const rightOf = (field: CardField, mode: CardFieldsMode): string | null =>
    mode === 'edit' ? field.editPermission : field.always ? null : field.permission;

/** Every right of that kind, for counting and for toggling everything at once. */
export const cardFieldRights = (groups: CardFieldGroup[], mode: CardFieldsMode): string[] =>
    groups.flatMap((group) => group.fields.map((field) => rightOf(field, mode)).filter((right) => right !== null));

/**
 * How much of a card a position reads or may change: for the button in the table.
 * The surname and the name are read by everybody, so in «Просмотр» they count as
 * ticked — the same boxes the dialog shows checked and locked.
 */
export function countCardFields(groups: CardFieldGroup[], held: string[], mode: CardFieldsMode): { chosen: number; total: number } {
    const rights = cardFieldRights(groups, mode);
    const always = mode === 'view' ? groups.flatMap((group) => group.fields).filter((field) => field.always).length : 0;

    return { chosen: rights.filter((right) => held.includes(right)).length + always, total: rights.length + always };
}

/**
 * Whether a position reads any line of a card it was actually given. The lines
 * everybody reads do not count here: they alone are no reason to open the staff
 * list, or every position would get it.
 */
export const readsAnyCardLine = (groups: CardFieldGroup[], held: string[]): boolean =>
    cardFieldRights(groups, 'view').some((right) => held.includes(right));

const HINTS: Record<CardFieldsMode, { title: string; description: string }> = {
    view: {
        title: 'Что видно в карточке',
        description:
            'Отмеченные строки эта позиция видит в карточке сотрудника, в колонках таблицы, в фильтрах и в поиске. Что не отмечено — не показывается нигде и не находится поиском. Фамилия и имя видны всегда, иначе список стал бы набором пустых строк. Своя карточка сотрудника — отдельный разговор: её строки выбираются в разделе «Профиль».',
    },
    edit: {
        title: 'Что можно менять',
        description:
            'Отмеченные строки эта позиция может править в карточке. Менять можно только то, что видно: строки, не отмеченные в «Просмотре», здесь недоступны — сначала откройте их для просмотра. Блок без единой разрешённой строки в карточке вообще не предлагает карандаш.',
    },
};

/**
 * Which lines of an employee card a position reads, or may change.
 *
 * The blocks are the ones a card is actually read in, so the answer here looks
 * like the page it governs. A block such as a passport is one answer; the two
 * blocks people pick apart — the main data and the contacts — open up.
 */
export function CardFields({
    mode,
    groups,
    held,
    onChange,
}: {
    mode: CardFieldsMode;
    groups: CardFieldGroup[];
    /** Every right the position holds; only those of this mode are touched. */
    held: string[];
    onChange: (permissions: string[]) => void;
}) {
    const [open, setOpen] = useState<string[]>([]);

    // Changing is allowed only where reading is: the right to read is what makes
    // a line available here at all.
    const locked = (field: CardField) => mode === 'edit' && !field.always && !held.includes(field.permission);

    /** A box that is ticked and cannot be unticked: a line nobody can close. */
    const fixed = (field: CardField) => mode === 'view' && field.always === true;

    /** Whether the box beside a line is ticked, the fixed ones included. */
    const ticked = (field: CardField) => fixed(field) || held.includes(rightOf(field, mode) ?? '');

    const set = (rights: string[], on: boolean) =>
        onChange(on ? [...held, ...rights.filter((right) => !held.includes(right))] : held.filter((right) => !rights.includes(right)));

    const toggleField = (field: CardField) => {
        const right = rightOf(field, mode);

        if (right !== null) {
            set([right], !held.includes(right));
        }
    };

    const toggleGroup = (group: CardFieldGroup) => {
        const rights = group.fields.filter((field) => !locked(field) && !fixed(field)).map((field) => rightOf(field, mode)!);
        const all = rights.length > 0 && rights.every((right) => held.includes(right));

        set(rights, !all);
    };

    const chosenIn = (group: CardFieldGroup) => group.fields.filter(ticked).length;

    return (
        <div className="grid gap-3">
            <p className="text-muted-foreground text-[13px]">{HINTS[mode].description}</p>

            <ul className="grid gap-2">
                {groups.map((group) => {
                    const chosen = chosenIn(group);
                    const all = chosen === group.fields.length;
                    const available = group.fields.filter((field) => !locked(field) && !fixed(field)).length;
                    const expanded = open.includes(group.key);

                    return (
                        <li key={group.key} className="border-border rounded-lg border">
                            <div className="flex items-center gap-2 px-3 py-2.5">
                                <Checkbox
                                    checked={all ? true : chosen > 0 ? 'indeterminate' : false}
                                    disabled={available === 0}
                                    onCheckedChange={() => toggleGroup(group)}
                                    aria-label={group.title}
                                />

                                {group.expandable ? (
                                    <button
                                        type="button"
                                        onClick={() => setOpen(expanded ? open.filter((key) => key !== group.key) : [...open, group.key])}
                                        aria-expanded={expanded}
                                        className="flex min-w-0 flex-1 items-center justify-between gap-2 text-left text-sm"
                                    >
                                        <span className="min-w-0 break-words">{group.title}</span>
                                        <span className="text-muted-foreground flex shrink-0 items-center gap-1 text-[13px] tabular-nums">
                                            {chosen} из {group.fields.length}
                                            <ChevronDown className={cn('size-4 transition-transform', expanded && 'rotate-180')} />
                                        </span>
                                    </button>
                                ) : (
                                    <span className="min-w-0 flex-1 text-sm break-words">{group.title}</span>
                                )}

                                {available === 0 && (
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <Info className="text-muted-foreground size-4 shrink-0" />
                                        </TooltipTrigger>
                                        <TooltipContent className="max-w-64">
                                            Блок закрыт для просмотра, поэтому менять его нечего. Откройте его в «Просмотре».
                                        </TooltipContent>
                                    </Tooltip>
                                )}
                            </div>

                            {group.expandable && (
                                <Collapsible open={expanded}>
                                    <CollapsibleContent>
                                        <ul className="grid gap-1.5 border-t px-3 py-2.5 pl-10">
                                            {group.fields.map((field) => (
                                                <li key={field.key}>
                                                    <label
                                                        // A little taller on a touch screen, so a finger finds
                                                        // the line it meant rather than its neighbour.
                                                        className={cn(
                                                            'flex items-center gap-2 py-1 text-sm max-md:min-h-10 max-md:py-2 lg:py-0',
                                                            locked(field) && 'text-muted-foreground',
                                                        )}
                                                    >
                                                        <Checkbox
                                                            checked={ticked(field)}
                                                            disabled={locked(field) || fixed(field)}
                                                            onCheckedChange={() => toggleField(field)}
                                                        />
                                                        {field.title}
                                                        {locked(field) && (
                                                            <Tooltip>
                                                                <TooltipTrigger asChild>
                                                                    <Info className="size-3.5 shrink-0" />
                                                                </TooltipTrigger>
                                                                <TooltipContent className="max-w-64">
                                                                    Эта строка закрыта для просмотра, поэтому её нельзя разрешить менять. Сначала
                                                                    отметьте её в «Просмотре».
                                                                </TooltipContent>
                                                            </Tooltip>
                                                        )}
                                                    </label>
                                                </li>
                                            ))}
                                        </ul>
                                    </CollapsibleContent>
                                </Collapsible>
                            )}
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}

/** The same list in a window of its own, for the table of positions against rights. */
export function CardFieldsDialog({
    mode,
    subject,
    groups,
    held,
    onChange,
    onClose,
}: {
    mode: CardFieldsMode;
    /** Whose card fields these are, e.g. the name of a position. */
    subject: string;
    groups: CardFieldGroup[];
    held: string[];
    onChange: (permissions: string[]) => void;
    onClose: () => void;
}) {
    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="scroll-soft max-h-[85svh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {HINTS[mode].title}: {subject}
                    </DialogTitle>
                    <DialogDescription>Изменения сохраняются сразу.</DialogDescription>
                </DialogHeader>

                <CardFields mode={mode} groups={groups} held={held} onChange={onChange} />

                <DialogFooter>
                    <Button variant="outline" className="max-md:h-11" onClick={onClose}>
                        Готово
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

/**
 * A cell of the table: how much of something a position holds, as a bar with a
 * count on it, and a click opens the list itself. The same face for every column
 * that keeps a list behind it, so a row of the table reads as answers of one kind.
 */
export function RightsButton({ chosen, total, title, onOpen }: { chosen: number; total: number; title: string; onOpen: () => void }) {
    const share = total === 0 ? 0 : Math.round((chosen / total) * 100);

    return (
        <button
            type="button"
            onClick={onOpen}
            title={`${title}: выбрано ${chosen} из ${total}`}
            className="hover:border-brand focus-visible:ring-ring mx-auto flex w-32 flex-col gap-1 rounded-md border px-2 py-1.5 outline-hidden transition-colors focus-visible:ring-2"
        >
            <span className="flex items-baseline justify-between gap-1 text-[13px]">
                <span className="tabular-nums">
                    {chosen} из {total}
                </span>
                <span className="text-muted-foreground tabular-nums">{share}%</span>
            </span>
            <span aria-hidden className="bg-muted h-1.5 overflow-hidden rounded-full">
                <span className="bg-brand block h-full rounded-full transition-[width]" style={{ width: `${share}%` }} />
            </span>
        </button>
    );
}

/** The same, counting the lines of a card a position reads or may change. */
export function CardFieldsButton({
    mode,
    groups,
    held,
    onOpen,
}: {
    mode: CardFieldsMode;
    groups: CardFieldGroup[];
    held: string[];
    onOpen: () => void;
}) {
    const { chosen, total } = countCardFields(groups, held, mode);

    return <RightsButton chosen={chosen} total={total} title={HINTS[mode].title} onOpen={onOpen} />;
}

/** One right of a plain list, as the server describes it. */
export interface PlainRight {
    key: string;
    title: string;
    hint: string;
    /**
     * A right this one is no use without — changing a list takes reading it. When
     * that one is missing the box is greyed out and says why, the way a line of a
     * card cannot be made editable before it is made visible.
     */
    requires?: { key: string; hint: string };
}

/**
 * A short list of rights as checkboxes, greying out whatever depends on a right
 * that is not held. Shared by the window over a position's row and by the dialog
 * of the position itself, so both refuse the same ticks.
 */
export function PlainRights({ rights, held, onToggle }: { rights: PlainRight[]; held: string[]; onToggle: (key: string) => void }) {
    return (
        <ul className="grid gap-2">
            {rights.map((right) => {
                const blocked = right.requires !== undefined && !held.includes(right.requires.key);

                return (
                    <li key={right.key}>
                        <label className={cn('flex items-start gap-2 text-sm', blocked && 'opacity-60')}>
                            <Checkbox
                                checked={held.includes(right.key)}
                                disabled={blocked}
                                onCheckedChange={() => onToggle(right.key)}
                                className="mt-0.5"
                            />
                            <span className="min-w-0">
                                {right.title}
                                <span className="text-muted-foreground block text-[13px]">{blocked ? right.requires!.hint : right.hint}</span>
                            </span>
                        </label>
                    </li>
                );
            })}
        </ul>
    );
}

/**
 * A short list of rights in a window of its own — what one does to a colleague
 * rather than to a line of their card. Kept behind the same counter as the
 * fields, so the row of a position reads as three answers of one kind instead of
 * two counters and a handful of loose boxes.
 */
export function RightsDialog({
    title,
    description,
    rights,
    held,
    onChange,
    onClose,
}: {
    title: string;
    description: string;
    rights: PlainRight[];
    held: string[];
    onChange: (permissions: string[]) => void;
    onClose: () => void;
}) {
    const toggle = (key: string) => onChange(held.includes(key) ? held.filter((right) => right !== key) : [...held, key]);

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            {/* The hints under each right make the list long enough to outgrow a
                phone held sideways, so the window scrolls inside itself. */}
            <DialogContent className="scroll-soft max-h-[85svh] overflow-y-auto sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>Изменения сохраняются сразу.</DialogDescription>
                </DialogHeader>

                <p className="text-muted-foreground text-[13px]">{description}</p>

                <PlainRights rights={rights} held={held} onToggle={toggle} />

                <DialogFooter>
                    <Button variant="outline" className="max-md:h-11" onClick={onClose}>
                        Готово
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
