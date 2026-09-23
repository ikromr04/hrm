<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The card of a unit, edited one block at a time, the way a profile is. What a
 * unit is and what comes with it are written here; where it is and who holds
 * it belong to the moves, so they stay out of these forms.
 */
class EquipmentDetailsController extends Controller
{
    /**
     * "Характеристики": what the unit is and what it cost.
     */
    public function specs(Request $request, Equipment $equipment): RedirectResponse
    {
        $data = $request->validate([
            'equipment_type_id' => ['required', 'integer', Rule::exists('equipment_types', 'id')],
            'name' => ['required', 'string', 'max:150'],
            'maker' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            // Still one unit, one number — this one's own does not clash with it.
            'inventory_number' => ['required', 'string', 'max:50', Rule::unique('equipment', 'inventory_number')->ignore($equipment)],
            'processor' => ['nullable', 'string', 'max:100'],
            'memory' => ['nullable', 'string', 'max:100'],
            'purchased_at' => ['nullable', 'date', 'before_or_equal:today'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'warranty_until' => ['nullable', 'date'],
        ], attributes: [
            'equipment_type_id' => 'категория',
            'name' => 'наименование',
            'maker' => 'производитель',
            'model' => 'модель',
            'serial_number' => 'серийный номер',
            'inventory_number' => 'инвентарный номер',
            'processor' => 'процессор',
            'memory' => 'память / диск',
            'purchased_at' => 'дата покупки',
            'price' => 'стоимость',
            'warranty_until' => 'гарантия до',
        ]);

        $equipment->update($data);

        return back();
    }

    /**
     * "Состояние": what state the unit was last seen in and when it is due to
     * be looked at again. A return fills this in by itself; this is for the
     * times somebody checks a unit without moving it.
     */
    public function state(Request $request, Equipment $equipment): RedirectResponse
    {
        $data = $request->validate([
            'condition' => ['nullable', 'string', 'max:200'],
            'checked_at' => ['nullable', 'date', 'before_or_equal:today'],
            'next_inventory_at' => ['nullable', 'date'],
        ], attributes: [
            'condition' => 'текущее состояние',
            'checked_at' => 'последняя проверка',
            'next_inventory_at' => 'следующая инвентаризация',
        ]);

        $equipment->update($data);

        return back();
    }

    /**
     * "Сейчас у сотрудника": a correction to the handover the unit is on, not a
     * move — the unit stays issued throughout. The open spell in the history is
     * corrected along with it, so the card and the history keep saying the same.
     */
    public function handover(Request $request, Equipment $equipment): RedirectResponse
    {
        abort_unless($equipment->status === 'issued', 422, 'Поправить выдачу можно только у выданного оборудования.');

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

        $holder = [
            'holder_user_id' => $data['holder_user_id'] ?? null,
            'holder_department_id' => $data['holder_department_id'] ?? null,
        ];

        $equipment->update([...$holder, 'issued_at' => $data['issued_at']]);

        // No open spell means the unit predates the history; start one now.
        $equipment->assignments()->updateOrCreate(
            ['id' => $equipment->currentAssignment?->id],
            [...$holder, 'issued_at' => $data['issued_at'], 'act_number' => $data['act_number'] ?? null],
        );

        return back();
    }

    /**
     * "Комплектация": the whole list is replaced, so removing a line is simply
     * leaving it out.
     */
    public function accessories(Request $request, Equipment $equipment): RedirectResponse
    {
        $data = $request->validate([
            'accessories' => ['present', 'array', 'max:30'],
            // A line left blank in the form arrives as null; it simply drops out.
            'accessories.*' => ['nullable', 'string', 'max:100'],
        ], attributes: ['accessories' => 'комплектация']);

        $items = array_filter(array_map(fn (?string $item) => trim($item ?? ''), $data['accessories']));

        $equipment->update(['accessories' => array_values($items)]);

        return back();
    }
}
