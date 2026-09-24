import { fieldLabel, listDiff, readValue, type EventChanges, type NameLookup } from '@/lib/equipment';
import { cn } from '@/lib/utils';

/**
 * What one journal entry recorded, read out field by field: "Статус: На балансе
 * → Выдано". A list-valued field — the accessories — reads as what was added
 * and what was taken away instead, so swapping one item for another says so
 * rather than printing both lists in full.
 */
export function ChangeLines({
    changes,
    names,
    note,
    className,
}: {
    changes: EventChanges;
    /** Names for the ids an entry kept, so a holder reads as a person. */
    names?: NameLookup;
    note?: string | null;
    className?: string;
}) {
    const fields = Object.entries(changes);

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
                        <span className="text-muted-foreground line-through">{readValue(field, before, names)}</span>
                        <span className="text-muted-foreground"> → </span>
                        <span className="font-medium">{readValue(field, after, names)}</span>
                    </li>
                );
            })}

            {note && <li className="text-[13px]">{note}</li>}
        </ul>
    );
}
