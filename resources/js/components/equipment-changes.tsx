import { fieldLabel, listDiff, readValue, statusLabel, type ChangeValue, type EventChanges, type EventKind, type NameLookup } from '@/lib/equipment';
import { cn } from '@/lib/utils';

/**
 * What each kind of entry leaves unsaid under its badge. A move's status only
 * repeats the badge above it; the dates a move clears or stamps — the issue
 * date it ends, the check date a return fills in — follow from the move itself
 * and are not news of their own.
 */
const unsaid: Partial<Record<EventKind, string[]>> = {
    issued: ['status', 'issued_at'],
    stocked: ['status', 'issued_at', 'checked_at'],
    written_off: ['status', 'issued_at', 'written_off_at'],
};

/**
 * What one journal entry recorded, read out field by field: "Статус: На балансе
 * → Выдано". A list-valued field — the accessories — reads as what was added
 * and what was taken away instead, so swapping one item for another says so
 * rather than printing both lists in full.
 */
export function ChangeLines({
    changes,
    kind,
    names,
    note,
    className,
}: {
    changes: EventChanges;
    /** What the entry was, for the fields that go without saying in it. */
    kind?: EventKind;
    /** Names for the ids an entry kept, so a holder reads as a person. */
    names?: NameLookup;
    note?: string | null;
    className?: string;
}) {
    // Entries written before all this was settled still hold those fields, and
    // leaving them out here means they read the same as the ones written since.
    // Nobody holding a unit is not a blank: it is the unit gone back on the
    // balance sheet, or off the books altogether if this is what struck it off.
    const nobody = kind === 'written_off' ? statusLabel.written_off : statusLabel.stock;
    const read = (field: string, value: ChangeValue) =>
        field === 'holder_user_id' && (value === null || value === '') ? nobody : readValue(field, value, names);

    const hidden = (kind && unsaid[kind]) ?? [];
    const fields = Object.entries(changes).filter(([field]) => !hidden.includes(field));

    if (fields.length === 0) {
        return note ? <span className={cn('text-[13px]', className)}>{note}</span> : <span className="text-muted-foreground">—</span>;
    }

    return (
        <ul className={cn('flex flex-col gap-0.5 whitespace-normal', className)}>
            {fields.map(([field, [before, after]]) => {
                const diff = listDiff(before, after);
                const label = fieldLabel[field] ?? field;

                if (diff) {
                    return (
                        <li key={field} className="text-[13px]">
                            <span className="text-muted-foreground">{label}: </span>
                            {diff.added.length > 0 && <span className="font-medium">добавлено {diff.added.join(', ')}</span>}
                            {diff.added.length > 0 && diff.removed.length > 0 && <span className="text-muted-foreground">; </span>}
                            {diff.removed.length > 0 && (
                                <span className="text-muted-foreground">
                                    убрано <span className="line-through">{diff.removed.join(', ')}</span>
                                </span>
                            )}
                            {diff.added.length === 0 && diff.removed.length === 0 && <span className="text-muted-foreground">порядок изменён</span>}
                        </li>
                    );
                }

                return (
                    <li key={field} className="text-[13px]">
                        <span className="text-muted-foreground">{label}: </span>
                        <span className="text-muted-foreground line-through">{read(field, before)}</span>
                        <span className="text-muted-foreground"> → </span>
                        <span className="font-medium">{read(field, after)}</span>
                    </li>
                );
            })}

            {note && <li className="text-[13px]">{note}</li>}
        </ul>
    );
}
