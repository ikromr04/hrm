<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\KeepsEquipmentPhotos;
use App\Http\Controllers\Concerns\SavesEquipmentFields;
use App\Models\Equipment;
use App\Models\EquipmentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * The card of a unit, edited one block at a time, the way a profile is. What a
 * unit is and what comes with it are written here; where it is and who holds
 * it belong to the moves, so they stay out of these forms.
 */
class EquipmentDetailsController extends Controller
{
    use KeepsEquipmentPhotos, SavesEquipmentFields;

    /**
     * "Характеристики": what the unit is.
     */
    public function specs(Request $request, Equipment $equipment): RedirectResponse
    {
        // The fields asked about are those of the category the form was filled
        // in for, which is not necessarily the one the unit is filed under now.
        $type = EquipmentType::with('fields')->find($request->integer('equipment_type_id'));

        $data = $request->validate([
            'equipment_type_id' => ['required', 'integer', Rule::exists('equipment_types', 'id')],
            'name' => ['required', 'string', 'max:150'],
            // Still one unit, one number — this one's own does not clash with it.
            'inventory_number' => ['required', 'string', 'max:50', Rule::unique('equipment', 'inventory_number')->ignore($equipment)],
            ...$this->fieldRules($type),
        ], attributes: [
            'equipment_type_id' => 'категория',
            'name' => 'наименование',
            'inventory_number' => 'инвентарный номер',
            ...$this->fieldAttributes($type),
        ]);

        // Which entries were there before, so a save that moved nothing but the
        // category's own fields can still be told it left a mark.
        $before = (int) $equipment->events()->max('id');

        // The category is set first: the values belong to the fields of the
        // category the unit ends up in.
        $equipment->equipment_type_id = $data['equipment_type_id'];
        $equipment->setRelation('type', $type);

        $equipment->journalExtra = $this->saveFields($equipment, $data['fields'] ?? []);
        $equipment->update(Arr::except($data, 'fields'));

        // Nothing but the fields moved, so the row's own save wrote no entry and
        // the journal would otherwise have nothing to say about the change.
        if ($equipment->journalExtra !== [] && (int) $equipment->events()->max('id') === $before) {
            $equipment->events()->create([
                'user_id' => $request->user()->id,
                'kind' => 'updated',
                'diff' => $equipment->journalExtra,
            ]);
        }

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
            ...$this->photoRules(),
        ], messages: $this->photoMessages(), attributes: [
            'condition' => 'текущее состояние',
            'checked_at' => 'последняя проверка',
            'next_inventory_at' => 'следующая инвентаризация',
            'photos' => 'фотографии',
        ]);

        // Which entries were there before, so the one this check writes can be
        // found afterwards and the photographs hung on it.
        $before = (int) $equipment->events()->max('id');

        $equipment->update(Arr::except($data, 'photos'));

        // A check with nothing to correct still happened, so it gets an entry
        // of its own rather than leaving the photographs with nowhere to hang.
        $this->keepPhotos($request, $equipment, $before, 'condition');

        return back();
    }

    /**
     * "Комплектация": the whole list is replaced, so removing a line is simply
     * leaving it out.
     */
    public function accessories(Request $request, Equipment $equipment): RedirectResponse
    {
        abort_unless($equipment->type?->has_accessories ?? false, 403, 'У этой категории нет комплектации.');

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
