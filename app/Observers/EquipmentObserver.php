<?php

namespace App\Observers;

use App\Models\Equipment;
use App\Models\EquipmentEvent;
use Illuminate\Support\Facades\Auth;

/**
 * Writes a unit's life into the journal as it happens. Every controller that
 * moves a unit does it by saving the row, so listening to the row catches all
 * of them — and catches whatever is added later without being told about it.
 */
class EquipmentObserver
{
    /** Changes worth nobody's attention: they follow from the rest. */
    private const IGNORED = ['updated_at', 'created_at'];

    /** The "Состояние" block of the card, edited together and named together. */
    private const STATE = ['condition', 'checked_at', 'next_inventory_at'];

    /**
     * The "Сейчас у сотрудника" block. A change of holder here is a handover
     * like any other, whatever form it was made in, and not the plain
     * correction the journal would otherwise call it.
     */
    private const HANDOVER = ['holder_user_id', 'issued_at'];

    /**
     * A unit joining the fleet. It lands on the balance sheet like a unit coming
     * back does, but the journal keeps the two apart: one is the day the company
     * bought the thing, the other is a Tuesday when somebody returned it.
     */
    public function created(Equipment $equipment): void
    {
        $equipment->events()->create([
            'user_id' => Auth::id(),
            'kind' => 'created',
            'note' => "Инв. № {$equipment->inventory_number}",
        ]);
    }

    public function updated(Equipment $equipment): void
    {
        // Both sides are read through the casts, so a date is a date and a
        // list is a list on either side of the arrow. What `getChanges` holds
        // is on its way to the database — for the accessories that is raw
        // JSON, which nobody wants to read.
        $changes = EquipmentEvent::diffOf($equipment, self::IGNORED);

        if ($changes === []) {
            return;
        }

        // A move is named by where it went. A correction is named by the block
        // of the card it was made in, so the journal reads as the card does;
        // a save that spans blocks is just a change.
        $fields = array_keys($changes);

        $kind = match (true) {
            array_key_exists('status', $changes) => match ($equipment->status) {
                'issued' => 'issued',
                'stock' => 'stocked',
                default => 'written_off',
            },
            array_diff($fields, self::STATE) === [] => 'condition',
            // Handing a unit to somebody else is a handover like any other, so
            // it is recorded as one. A date on its own is not: that is a
            // correction to the date, and it stays a plain change.
            array_key_exists('holder_user_id', $changes) && array_diff($fields, self::HANDOVER) === [] => 'issued',
            $fields === ['accessories'] => 'accessories',
            default => 'updated',
        };

        $equipment->events()->create([
            'user_id' => Auth::id(),
            'kind' => $kind,
            'diff' => $changes,
            'note' => $equipment->journalNote,
        ]);

        // Said once, for the save that asked for it.
        $equipment->journalNote = null;
    }
}
