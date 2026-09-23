<?php

namespace App\Http\Controllers\Directories;

use App\Http\Controllers\Controller;
use App\Models\EquipmentType;
use App\Models\UserEquipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kinds of hardware the company hands out; the units themselves live on the
 * employee, each with its own inventory number.
 */
class EquipmentTypeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('directories/equipment', [
            // Holders, not units: someone with two monitors counts once, and
            // only working staff are counted.
            'items' => EquipmentType::query()
                ->select(['id', 'name'])
                ->addSelect(['users_count' => UserEquipment::query()
                    ->selectRaw('count(distinct user_equipment.user_id)')
                    ->join('users', 'users.id', '=', 'user_equipment.user_id')
                    ->whereColumn('user_equipment.equipment_type_id', 'equipment_types.id')
                    ->where('users.status', 'active'),
                ])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        EquipmentType::create($this->validated($request));

        return back();
    }

    public function update(Request $request, EquipmentType $equipment): RedirectResponse
    {
        $equipment->update($this->validated($request, $equipment));

        return back();
    }

    public function destroy(EquipmentType $equipment): RedirectResponse
    {
        // The units of this kind go with it; employees themselves are untouched.
        $equipment->delete();

        return back();
    }

    /**
     * @return array{name: string}
     */
    private function validated(Request $request, ?EquipmentType $type = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('equipment_types', 'name')->ignore($type)],
        ], attributes: ['name' => 'название']);
    }
}
