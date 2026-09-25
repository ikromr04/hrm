<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Equipment;
use App\Models\EquipmentEvent;
use App\Models\EquipmentPhoto;
use App\Models\EquipmentRepair;
use Illuminate\Http\Request;

/**
 * Every form that records something about a unit may carry pictures of it, and
 * they all keep them the same way: on the journal entry for that occasion, so a
 * photograph belongs to the moment it was taken rather than floating beside the
 * unit with no date on it.
 */
trait KeepsEquipmentPhotos
{
    /**
     * The same limits in every window, so a picture from a phone is never
     * refused in one form and taken in another.
     */
    protected const PHOTO_RULES = [
        'photos' => ['nullable', 'array', 'max:10'],
        'photos.*' => ['image', 'mimes:jpeg,png,webp,heic', 'max:12288'],
    ];

    /**
     * Hangs the upload on the entry a save has just written. `$since` is the
     * newest entry from before the save; `$kind` names the entry to write if
     * the save changed nothing and the observer stayed silent, which is what
     * happens when somebody only adds a photograph.
     */
    protected function keepPhotos(Request $request, Equipment $equipment, int $since, string $kind): void
    {
        if (($request->file('photos') ?? []) === []) {
            return;
        }

        $event = $equipment->events()->where('id', '>', $since)->latest('id')->first()
            ?? $equipment->events()->create(['user_id' => $request->user()->id, 'kind' => $kind]);

        $this->attachPhotos($request, $equipment, $event);
    }

    /**
     * The same, for a form that has written its own entry already. A record of
     * service work is named as well, so its pictures are shown on the record
     * and not only in the journal.
     */
    protected function attachPhotos(Request $request, Equipment $equipment, EquipmentEvent $event, ?EquipmentRepair $repair = null): void
    {
        foreach ($request->file('photos') ?? [] as $photo) {
            EquipmentPhoto::keep($equipment, $event, $photo, $repair);
        }
    }
}
