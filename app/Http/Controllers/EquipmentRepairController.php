<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentRepair;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * What has been done to a unit: planned maintenance as well as breakdowns. A
 * repair that has not ended also moves the unit out of service, so the card
 * and the list agree on where it is.
 */
class EquipmentRepairController extends Controller
{
    public function store(Request $request, Equipment $equipment): RedirectResponse
    {
        abort_if($equipment->status === 'written_off', 422, 'Списанное оборудование нельзя отправить в ремонт.');

        $data = $request->validate([
            'kind' => ['required', 'string', 'max:150'],
            'started_at' => ['required', 'date', 'before_or_equal:today'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'note' => ['nullable', 'string', 'max:200'],
        ], attributes: [
            'kind' => 'тип работ',
            'started_at' => 'дата начала',
            'ended_at' => 'дата окончания',
            'note' => 'комментарий',
        ]);

        $repair = $equipment->repairs()->create($data);

        // Still away: the unit is in repair and nobody holds it meanwhile.
        $leaves = ($data['ended_at'] ?? null) === null && $equipment->status !== 'repair';

        if ($leaves) {
            $equipment->currentAssignment?->update(['returned_at' => $data['started_at']]);

            // The move is the journal entry, and it carries the reason, so
            // sending a unit away reads as one act rather than two.
            $equipment->journalNote = $repair->kind;
            $equipment->update([
                'status' => 'repair',
                'holder_user_id' => null,
                'holder_department_id' => null,
                'issued_at' => null,
            ]);

            return back();
        }

        // A visit that is already over moves nothing, so it says so itself.
        $equipment->events()->create([
            'user_id' => $request->user()->id,
            'kind' => 'repair_added',
            'note' => $repair->kind,
        ]);

        return back();
    }

    public function destroy(Request $request, Equipment $equipment, EquipmentRepair $repair): RedirectResponse
    {
        abort_if($repair->equipment_id !== $equipment->id, 404);

        $equipment->events()->create([
            'user_id' => $request->user()->id,
            'kind' => 'repair_removed',
            'note' => $repair->kind,
        ]);

        $repair->delete();

        return back();
    }
}
