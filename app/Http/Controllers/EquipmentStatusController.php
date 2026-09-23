<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * A unit's life: handed out, taken back, sent for repair, written off. Each
 * move sets the status and the holder together, so the two never disagree.
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
        ], attributes: [
            'holder_user_id' => 'сотрудник',
            'holder_department_id' => 'отдел',
            'issued_at' => 'дата выдачи',
        ]);

        $this->stillInService($equipment);

        $equipment->update([
            'status' => 'issued',
            'holder_user_id' => $data['holder_user_id'] ?? null,
            'holder_department_id' => $data['holder_department_id'] ?? null,
            'issued_at' => $data['issued_at'],
        ]);

        return back();
    }

    /**
     * Back in stock: nobody holds it any more.
     */
    public function take(Equipment $equipment): RedirectResponse
    {
        $this->release($equipment, 'stock');

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
    private function release(Equipment $equipment, string $status): void
    {
        $this->stillInService($equipment);

        $equipment->update([
            'status' => $status,
            'holder_user_id' => null,
            'holder_department_id' => null,
            'issued_at' => null,
        ]);
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
     * A written-off unit is gone: it is not handed out, returned or repaired.
     */
    private function stillInService(Equipment $equipment): void
    {
        abort_if($equipment->status === 'written_off', 422, 'Списанное оборудование нельзя перемещать.');
    }
}
