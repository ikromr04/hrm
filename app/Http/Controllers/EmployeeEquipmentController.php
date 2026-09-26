<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Handing a colleague what they need to work with, several units at once —
 * the last step of the "new colleague" wizard, where a workplace is put
 * together in one go rather than a unit at a time from the fleet.
 */
class EmployeeEquipmentController extends Controller
{
    public function store(Request $request, User $employee): RedirectResponse
    {
        $data = $request->validate([
            'equipment' => ['present', 'array', 'max:20'],
            'equipment.*' => ['integer', 'distinct', Rule::exists('equipment', 'id')],
            'issued_at' => ['nullable', 'date', 'before_or_equal:today'],
        ], attributes: [
            'equipment' => 'оборудование',
            'issued_at' => 'дата выдачи',
        ]);

        $issuedAt = $data['issued_at'] ?? Carbon::today()->toDateString();

        Equipment::query()->whereIn('id', $data['equipment'])->get()->each(function (Equipment $unit) use ($employee, $issuedAt) {
            // Only what is free may be handed over; anything else is left alone
            // rather than quietly taken from whoever has it.
            if ($unit->status !== 'stock') {
                return;
            }

            $unit->update([
                'status' => 'issued',
                'holder_user_id' => $employee->id,
                'issued_at' => $issuedAt,
            ]);
        });

        return back();
    }
}
