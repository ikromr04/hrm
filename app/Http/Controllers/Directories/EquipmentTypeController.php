<?php

namespace App\Http\Controllers\Directories;

use App\Http\Controllers\Controller;
use App\Models\EquipmentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Categories of hardware ("Ноутбуки", "Мониторы"); the units themselves live
 * in the equipment section, each with its own inventory number.
 */
class EquipmentTypeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('directories/equipment', [
            // Units in the category, written-off ones aside: the directory
            // counts hardware, and the number links nowhere else.
            'items' => EquipmentType::query()
                ->select(['id', 'name'])
                ->withCount(['equipment as users_count' => fn ($q) => $q->inService()])
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
