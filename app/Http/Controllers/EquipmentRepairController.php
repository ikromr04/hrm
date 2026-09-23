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
            'contractor' => ['nullable', 'string', 'max:150'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'note' => ['nullable', 'string', 'max:200'],
        ], attributes: [
            'kind' => 'тип работ',
            'started_at' => 'дата начала',
            'ended_at' => 'дата окончания',
            'contractor' => 'подрядчик',
            'cost' => 'стоимость',
            'note' => 'комментарий',
        ]);

        $equipment->repairs()->create($data);

        // Still away: the unit is in repair and nobody holds it meanwhile.
        if (($data['ended_at'] ?? null) === null && $equipment->status !== 'repair') {
            $equipment->currentAssignment?->update(['returned_at' => $data['started_at']]);

            $equipment->update([
                'status' => 'repair',
                'holder_user_id' => null,
                'holder_department_id' => null,
                'issued_at' => null,
            ]);
        }

        return back();
    }

    public function destroy(Equipment $equipment, EquipmentRepair $repair): RedirectResponse
    {
        abort_if($repair->equipment_id !== $equipment->id, 404);

        $repair->delete();

        return back();
    }
}
