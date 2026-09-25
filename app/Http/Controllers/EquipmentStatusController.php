<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * A unit's life: handed out, taken back, written off. Each
 * move sets the status and the holder together, so the two never disagree, and
 * writes the spell it ends into the unit's history.
 */
class EquipmentStatusController extends Controller
{
    /**
     * Handed to one employee or to a whole department — the design shows both,
     * "Фарход Рахимов" and "Отдел бухгалтерии" — but never to the two at once.
     */
    public function issue(Request $request, Equipment $equipment): RedirectResponse
    {
        $data = $request->validate([
            'holder_user_id' => ['nullable', 'required_without:holder_department_id', 'prohibits:holder_department_id', 'integer', Rule::exists('users', 'id')],
            'holder_department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'issued_at' => ['required', 'date', 'before_or_equal:today'],
            ...self::PHOTO_RULES,
        ], attributes: [
            'holder_user_id' => 'сотрудник',
            'holder_department_id' => 'отдел',
            'issued_at' => 'дата выдачи',
            'photos' => 'фотографии',
        ]);

        $this->stillInService($equipment);
        $this->closeSpell($equipment, $data['issued_at']);

        $before = (int) $equipment->events()->max('id');

        $equipment->update([
            'status' => 'issued',
            'holder_user_id' => $data['holder_user_id'] ?? null,
            'holder_department_id' => $data['holder_department_id'] ?? null,
            'issued_at' => $data['issued_at'],
        ]);

        $equipment->assignments()->create([
            'holder_user_id' => $data['holder_user_id'] ?? null,
            'holder_department_id' => $data['holder_department_id'] ?? null,
            'issued_at' => $data['issued_at'],
        ]);

        $this->keepPhotos($request, $equipment, $before, 'issued');

        return back();
    }

    /**
     * Back in stock: nobody holds it any more. Whoever brought it back may say
     * what state it is in, which is what the history column shows.
     */
    public function take(Request $request, Equipment $equipment): RedirectResponse
    {
        $data = $request->validate([
            'condition_on_return' => ['nullable', 'string', 'max:200'],
            ...self::PHOTO_RULES,
        ], attributes: [
            'condition_on_return' => 'состояние при возврате',
            'photos' => 'фотографии',
        ]);

        $before = (int) $equipment->events()->max('id');

        $this->release($equipment, 'stock', $data['condition_on_return'] ?? null);

        // Sitting in stock is a spell of its own, so the history reads in full.
        $equipment->assignments()->create(['issued_at' => Carbon::today()]);

        $this->keepPhotos($request, $equipment, $before, 'taken');

        return back();
    }

    /**
     * Nobody holds it now, whatever the reason.
     */
    private function release(Equipment $equipment, string $status, ?string $condition = null): void
    {
        $this->stillInService($equipment);
        $this->closeSpell($equipment, Carbon::today()->toDateString(), $condition);

        // One save, so the journal reads the return as one act rather than as
        // a move followed by a correction.
        $equipment->update([
            'status' => $status,
            'holder_user_id' => null,
            'holder_department_id' => null,
            'issued_at' => null,
            ...$condition === null ? [] : ['condition' => $condition, 'checked_at' => Carbon::today()],
        ]);
    }

    /**
     * Out of the fleet for good.
     */
    public function writeOff(Request $request, Equipment $equipment): RedirectResponse
    {
        $data = $request->validate([
            'written_off_at' => ['required', 'date', 'before_or_equal:today'],
            ...self::PHOTO_RULES,
        ], attributes: [
            'written_off_at' => 'дата списания',
            'photos' => 'фотографии',
        ]);

        $this->stillInService($equipment);
        $this->closeSpell($equipment, $data['written_off_at']);

        $before = (int) $equipment->events()->max('id');

        $equipment->update([
            'status' => 'written_off',
            'holder_user_id' => null,
            'holder_department_id' => null,
            'issued_at' => null,
            'written_off_at' => $data['written_off_at'],
        ]);

        $this->keepPhotos($request, $equipment, $before, 'written_off');

        return back();
    }

    /**
     * Every move may carry pictures: what went out, what came back, what is
     * being struck off. The same limits everywhere, so a phone's photograph is
     * never refused in one window and taken in another.
     */
    private const PHOTO_RULES = [
        'photos' => ['nullable', 'array', 'max:10'],
        'photos.*' => ['image', 'mimes:jpeg,png,webp,heic', 'max:12288'],
    ];

    /**
     * Hangs whatever was photographed on the entry this move has just written,
     * so the pictures belong to the occasion rather than floating beside the
     * unit. `$before` is the newest entry from before the move; `$kind` names
     * the entry to write if the move changed nothing and the observer stayed
     * silent.
     */
    private function keepPhotos(Request $request, Equipment $equipment, int $before, string $kind): void
    {
        $photos = $request->file('photos') ?? [];

        if ($photos === []) {
            return;
        }

        $event = $equipment->events()->where('id', '>', $before)->latest('id')->first()
            ?? $equipment->events()->create(['user_id' => $request->user()->id, 'kind' => $kind]);

        foreach ($photos as $photo) {
            EquipmentPhoto::keep($equipment, $event, $photo);
        }
    }

    /**
     * End whichever spell is open, so a unit is never in two places at once.
     * The return date never precedes the handover it closes.
     */
    private function closeSpell(Equipment $equipment, string $on, ?string $condition = null): void
    {
        $open = $equipment->currentAssignment;

        $open?->update([
            'returned_at' => max($on, $open->issued_at->toDateString()),
            'condition_on_return' => $condition,
        ]);
    }

    /**
     * A written-off unit is gone: it is not handed out and not taken back.
     */
    private function stillInService(Equipment $equipment): void
    {
        abort_if($equipment->status === 'written_off', 422, 'Списанное оборудование нельзя перемещать.');
    }
}
