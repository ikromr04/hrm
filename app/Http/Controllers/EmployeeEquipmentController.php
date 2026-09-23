<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserEquipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * One unit of hardware at a time, the way the tab lists them: handing out a
 * monitor should not rewrite the rest of the person's kit.
 */
class EmployeeEquipmentController extends Controller
{
    public function store(Request $request, User $employee): RedirectResponse
    {
        $employee->equipment()->create($this->validated($request));

        return back();
    }

    public function update(Request $request, User $employee, UserEquipment $unit): RedirectResponse
    {
        $this->belongsTo($employee, $unit);
        $unit->update($this->validated($request, $unit));

        return back();
    }

    public function destroy(User $employee, UserEquipment $unit): RedirectResponse
    {
        $this->belongsTo($employee, $unit);
        $unit->delete();

        return back();
    }

    /**
     * The record id travels beside an employee id, so the two must agree:
     * otherwise one person's URL would reach another person's record.
     */
    private function belongsTo(User $employee, UserEquipment $unit): void
    {
        abort_unless($unit->user_id === $employee->id, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?UserEquipment $unit = null): array
    {
        return $request->validate([
            'equipment_type_id' => ['required', 'integer', Rule::exists('equipment_types', 'id')],
            'description' => ['nullable', 'string', 'max:255'],
            // One physical unit, one holder: the number must be free across the
            // company, though the unit being edited does not clash with itself.
            'inventory_number' => ['required', 'string', 'max:50', Rule::unique('user_equipment', 'inventory_number')->ignore($unit)],
        ], attributes: [
            'equipment_type_id' => 'оборудование',
            'description' => 'описание',
            'inventory_number' => 'инвентарный номер',
        ]);
    }
}
