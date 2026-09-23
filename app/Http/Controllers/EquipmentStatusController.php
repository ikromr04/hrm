<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * A unit's life: handed out, taken back, sent for repair, written off. Each
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
            'act_number' => ['nullable', 'string', 'max:50'],
        ], attributes: [
            'holder_user_id' => 'сотрудник',
            'holder_department_id' => 'отдел',
            'issued_at' => 'дата выдачи',
            'act_number' => 'акт передачи',
        ]);

        $this->stillInService($equipment);
        $this->closeSpell($equipment, $data['issued_at']);

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
            'act_number' => $data['act_number'] ?? null,
        ]);

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
        ], attributes: ['condition_on_return' => 'состояние при возврате']);

        $this->release($equipment, 'stock', $data['condition_on_return'] ?? null);

        // Sitting in stock is a spell of its own, so the history reads in full.
        $equipment->assignments()->create(['issued_at' => Carbon::today()]);

        return back();
    }

    /**
     * Away at a contractor; whoever held it no longer does.
     */
    public function repair(Equipment $equipment): RedirectResponse
    {
        $this->release($equipment, 'repair');

        return back();
    }

    /**
     * Nobody holds it now, whatever the reason.
     */
    private function release(Equipment $equipment, string $status, ?string $condition = null): void
    {
        $this->stillInService($equipment);
        $this->closeSpell($equipment, Carbon::today()->toDateString(), $condition);

        $equipment->update([
            'status' => $status,
            'holder_user_id' => null,
            'holder_department_id' => null,
            'issued_at' => null,
        ]);

        if ($condition !== null) {
            $equipment->update(['condition' => $condition, 'checked_at' => Carbon::today()]);
        }
    }

    /**
     * Out of the fleet for good.
     */
    public function writeOff(Request $request, Equipment $equipment): RedirectResponse
    {
        $data = $request->validate([
            'written_off_at' => ['required', 'date', 'before_or_equal:today'],
        ], attributes: ['written_off_at' => 'дата списания']);

        $this->stillInService($equipment);
        $this->closeSpell($equipment, $data['written_off_at']);

        $equipment->update([
            'status' => 'written_off',
            'holder_user_id' => null,
            'holder_department_id' => null,
            'issued_at' => null,
            'written_off_at' => $data['written_off_at'],
        ]);

        return back();
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
     * A written-off unit is gone: it is not handed out, returned or repaired.
     */
    private function stillInService(Equipment $equipment): void
    {
        abort_if($equipment->status === 'written_off', 422, 'Списанное оборудование нельзя перемещать.');
    }
}
