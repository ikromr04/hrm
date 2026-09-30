<?php

namespace App\Policies;

use App\Models\Equipment;
use App\Models\User;
use App\Support\EquipmentAccess;

/**
 * Whether a unit is one this person may open.
 *
 * The fleet is seen in parts — one's own, one's department's, all of it — so a
 * card is not opened by the right to the section but by whether the unit falls
 * inside a part the viewer holds. Administrators pass through Gate::before.
 */
class EquipmentPolicy
{
    public function view(User $viewer, Equipment $unit): bool
    {
        return EquipmentAccess::canSee($viewer, $unit);
    }

    /** Whether what happened to it is theirs to read. */
    public function viewJournal(User $viewer, Equipment $unit): bool
    {
        return EquipmentAccess::canReadJournalOf($viewer, $unit);
    }

    /**
     * The card is changed block by block. Each block is a right of its own, and
     * all of them still take seeing the unit.
     */
    public function editSpecs(User $viewer, Equipment $unit): bool
    {
        return EquipmentAccess::canEditBlock($viewer, $unit, 'specs');
    }

    public function editAccessories(User $viewer, Equipment $unit): bool
    {
        return EquipmentAccess::canEditBlock($viewer, $unit, 'accessories');
    }

    public function editState(User $viewer, Equipment $unit): bool
    {
        return EquipmentAccess::canEditBlock($viewer, $unit, 'state');
    }

    /** The moves: each one is handed out separately. */
    public function issue(User $viewer, Equipment $unit): bool
    {
        return EquipmentAccess::canDo($viewer, 'issue', $unit);
    }

    public function take(User $viewer, Equipment $unit): bool
    {
        return EquipmentAccess::canDo($viewer, 'take', $unit);
    }

    public function writeOff(User $viewer, Equipment $unit): bool
    {
        return EquipmentAccess::canDo($viewer, 'write_off', $unit);
    }

    /** Records of repair, all four of them under one right. */
    public function service(User $viewer, Equipment $unit): bool
    {
        return EquipmentAccess::canDo($viewer, 'service', $unit);
    }

    public function delete(User $viewer, Equipment $unit): bool
    {
        return EquipmentAccess::canDo($viewer, 'delete', $unit);
    }
}
