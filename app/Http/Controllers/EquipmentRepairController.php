<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentEvent;
use App\Models\EquipmentPhoto;
use App\Models\EquipmentRepair;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * What has been done to a unit: planned maintenance as well as breakdowns.
 * A record is a note in the unit's history, nothing more — it does not move
 * the unit or take it from whoever holds it, because a keyboard cleaned at the
 * desk and a laptop away at a shop are both service, and the card would have
 * to lie about one of them.
 */
class EquipmentRepairController extends Controller
{
    public function store(Request $request, Equipment $equipment): RedirectResponse
    {
        abort_if($equipment->status === 'written_off', 422, 'Списанное оборудование нельзя обслуживать.');

        $data = $this->validated($request);

        $repair = $equipment->repairs()->create(Arr::except($data, 'photos'));

        $event = $equipment->events()->create([
            'user_id' => $request->user()->id,
            'kind' => 'repair_added',
            'note' => $repair->kind,
        ]);

        $this->keepPhotos($request, $equipment, $event, $repair);

        return back();
    }

    /**
     * Correcting a record — most often filling in the end date, which is what
     * takes the unit off the list of what is being looked after.
     */
    public function update(Request $request, Equipment $equipment, EquipmentRepair $repair): RedirectResponse
    {
        abort_if($repair->equipment_id !== $equipment->id, 404);

        $data = $this->validated($request);

        $repair->update(Arr::except($data, 'photos'));

        $event = $equipment->events()->create([
            'user_id' => $request->user()->id,
            // Finishing the work is the change worth naming; anything else is
            // a correction, and the entry says as much either way.
            'kind' => $repair->wasChanged('ended_at') && $repair->ended_at !== null ? 'repair_ended' : 'repair_updated',
            'note' => $repair->kind,
        ]);

        // Later pictures join the record; they never replace the earlier ones.
        $this->keepPhotos($request, $equipment, $event, $repair);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'kind' => ['required', 'string', 'max:150'],
            'started_at' => ['required', 'date', 'before_or_equal:today'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'note' => ['nullable', 'string', 'max:200'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['image', 'mimes:jpeg,png,webp,heic', 'max:12288'],
        ], attributes: [
            'kind' => 'тип работ',
            'started_at' => 'дата начала',
            'ended_at' => 'дата окончания',
            'note' => 'комментарий',
            'photos' => 'фотографии',
        ]);
    }

    /**
     * What was photographed belongs both to the entry in the journal and to the
     * record it came with, so it can be read from either side.
     */
    private function keepPhotos(Request $request, Equipment $equipment, EquipmentEvent $event, EquipmentRepair $repair): void
    {
        foreach ($request->file('photos') ?? [] as $photo) {
            EquipmentPhoto::keep($equipment, $event, $photo, $repair);
        }
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
