import { RightsButton } from '@/components/card-fields';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

/** One part of the fleet, with the right to see it and the right to its journal. */
export interface EquipmentScope {
    key: string;
    title: string;
    hint: string;
    /** "equipment.view.own" */
    permission: string;
    journal: {
        title: string;
        hint: string;
        /** "equipment.journal.own" */
        permission: string;
    };
}

/** Every right a scope carries, for counting and for the button in the table. */
export const equipmentScopeRights = (scopes: EquipmentScope[]): string[] => scopes.flatMap((scope) => [scope.permission, scope.journal.permission]);

export function countEquipmentScopes(scopes: EquipmentScope[], held: string[]): { chosen: number; total: number } {
    const rights = equipmentScopeRights(scopes);

    return { chosen: rights.filter((right) => held.includes(right)).length, total: rights.length };
}

const DESCRIPTION =
    'Техника видна по частям: своё, оборудование своего отдела — это для руководителя — и всё сразу. У каждой части отдельно решается журнал операций: одно дело знать, что у тебя на руках, другое — кто держал эту единицу до тебя.';

/**
 * How much of the fleet a position sees.
 *
 * Three answers rather than one, because three different people ask: a colleague
 * about their own desk, a head of department about their people, and whoever
 * keeps the books about everything. The journal hangs off the part it belongs
 * to, and cannot be opened without it — a journal of units one may not see would
 * tell the story without showing the thing.
 */
export function EquipmentScopes({
    scopes,
    held,
    onChange,
}: {
    scopes: EquipmentScope[];
    /** Every right the position holds; only those of this section are touched. */
    held: string[];
    onChange: (permissions: string[]) => void;
}) {
    const set = (rights: string[], on: boolean) =>
        onChange(on ? [...held, ...rights.filter((right) => !held.includes(right))] : held.filter((right) => !rights.includes(right)));

    const toggleScope = (scope: EquipmentScope) =>
        // Closing a part closes its journal with it: the journal is about those
        // units, and there would be none left to read about.
        held.includes(scope.permission) ? set([scope.permission, scope.journal.permission], false) : set([scope.permission], true);

    return (
        <div className="grid gap-3">
            <p className="text-muted-foreground text-[13px]">{DESCRIPTION}</p>

            <ul className="grid gap-2">
                {scopes.map((scope) => {
                    const open = held.includes(scope.permission);

                    return (
                        <li key={scope.key} className="border-border rounded-lg border px-3 py-2.5">
                            <label className="flex items-start gap-2 text-sm">
                                <Checkbox checked={open} onCheckedChange={() => toggleScope(scope)} className="mt-0.5" />
                                <span className="min-w-0">
                                    {scope.title}
                                    <span className="text-muted-foreground block text-[13px]">{scope.hint}</span>
                                </span>
                            </label>

                            {open && (
                                <label className="mt-2 ml-6 flex items-start gap-2 border-t pt-2 text-sm">
                                    <Checkbox
                                        checked={held.includes(scope.journal.permission)}
                                        onCheckedChange={() => set([scope.journal.permission], !held.includes(scope.journal.permission))}
                                        className="mt-0.5"
                                    />
                                    <span className="min-w-0">
                                        {scope.journal.title}
                                        <span className="text-muted-foreground block text-[13px]">{scope.journal.hint}</span>
                                    </span>
                                </label>
                            )}
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}

/** The same list in a window of its own, for the table of positions against rights. */
export function EquipmentScopesDialog({
    subject,
    scopes,
    held,
    onChange,
    onClose,
}: {
    subject: string;
    scopes: EquipmentScope[];
    held: string[];
    onChange: (permissions: string[]) => void;
    onClose: () => void;
}) {
    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="scroll-soft max-h-[85svh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Что видно в оборудовании: {subject}</DialogTitle>
                    <DialogDescription>Изменения сохраняются сразу.</DialogDescription>
                </DialogHeader>

                <EquipmentScopes scopes={scopes} held={held} onChange={onChange} />

                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>
                        Готово
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

/** The cell of the table, counting the same way the other sections do. */
export function EquipmentScopesButton({ scopes, held, onOpen }: { scopes: EquipmentScope[]; held: string[]; onOpen: () => void }) {
    const { chosen, total } = countEquipmentScopes(scopes, held);

    return <RightsButton chosen={chosen} total={total} title="Что видно в оборудовании" onOpen={onOpen} />;
}
