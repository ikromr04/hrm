<?php

namespace App\Observers;

use App\Models\Equipment;
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
     * The "Сейчас у сотрудника" block. Changed on its own it is a
     * reassignment — the unit stays out, it is somebody else who answers for
     * it now — and not the plain correction the journal would otherwise call it.
     */
    private const HANDOVER = ['holder_user_id', 'issued_at'];

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
        $changes = collect($equipment->getChanges())
            ->except(self::IGNORED)
            ->map(fn ($ignored, string $field) => [
                $this->plain($equipment->getOriginal($field)),
                $this->plain($equipment->getAttribute($field)),
            ])
            ->all();

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
                'stock' => 'taken',
                default => 'written_off',
            },
            array_diff($fields, self::STATE) === [] => 'condition',
            array_diff($fields, self::HANDOVER) === [] => 'reassigned',
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

    /**
     * Dates and decimals come off the row in whatever shape the driver gives
     * them; the journal keeps plain values, so a line reads the same however
     * it was written. A list stays a list: the accessories are compared item
     * by item when the entry is read, to say what was added and what was not.
     */
    private function plain(mixed $value): string|int|float|bool|array|null
    {
        return match (true) {
            $value === null || is_scalar($value) => $value,
            $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
            is_array($value) => array_values(array_map(fn ($item) => (string) $item, $value)),
            default => (string) $value,
        };
    }
}
