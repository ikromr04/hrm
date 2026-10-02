<?php

namespace App\Notifications;

use App\Models\Equipment;

/**
 * A unit came to somebody or left them, by somebody else's hand: handed over,
 * taken back, or written off while it was still on them. The person answers
 * for the unit by name, so they are told when that starts and when it ends.
 */
class EquipmentMoved extends InAppNotification
{
    /** What each move is called from the side of the person it happened to. */
    private const SENTENCES = [
        'issued' => 'Вам выдано',
        'taken' => 'У вас принято',
        'written_off' => 'У вас списано',
    ];

    /**
     * @param  'issued'|'taken'|'written_off'  $move
     */
    public function __construct(private readonly Equipment $unit, private readonly string $move) {}

    protected function kind(): string
    {
        return "equipment.{$this->move}";
    }

    protected function text(): string
    {
        return self::SENTENCES[$this->move].": {$this->unit->name}, инв. № {$this->unit->inventory_number}";
    }

    protected function target(): array
    {
        return ['type' => 'equipment', 'id' => $this->unit->id];
    }
}
