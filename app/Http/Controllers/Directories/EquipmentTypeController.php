<?php

namespace App\Http\Controllers\Directories;

use App\Http\Controllers\Controller;
use App\Models\EquipmentField;
use App\Models\EquipmentType;
use App\Support\Directories;
use App\Support\EquipmentIcons;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Categories of hardware ("Ноутбуки", "Мониторы"); the units themselves live
 * in the equipment section, each with its own inventory number.
 *
 * A category also decides what its units are described by: a monitor has a
 * diagonal and no processor, a phone has an IMEI. Those fields are set here,
 * which is why the dialog of this directory is the longest of them all.
 */
class EquipmentTypeController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('directories/equipment', [
            // What a category may be drawn by; the form offers exactly these.
            'icons' => EquipmentIcons::KEYS,
            // What a field can hold, for the form's own list of types.
            'fieldTypes' => collect(EquipmentField::TYPES)->map(fn (string $title, string $key) => ['key' => $key, 'title' => $title])->values(),
            // What a new category is offered to start from, so nobody types out
            // "Производитель" and "Модель" for the hundredth time.
            'defaultFields' => collect(EquipmentField::DEFAULTS)
                ->map(fn (array $field) => [...$field, 'options' => [], 'required' => false])
                ->all(),
            // Units in the category, written-off ones aside: the directory
            // counts hardware, and the number links nowhere else.
            // Reading a list and keeping it are two rights, so the page says
            // which one it is looking at.
            'canEdit' => Directories::canEdit($request->user(), 'equipment'),
            'items' => EquipmentType::query()
                ->with('fields')
                ->withCount(['equipment as users_count' => fn ($q) => $q->inService()])
                ->orderBy('name')
                ->get()
                ->map(fn (EquipmentType $type) => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'icon' => $type->icon,
                    'has_accessories' => $type->has_accessories,
                    'users_count' => $type->users_count,
                    'fields' => $type->fields->map(fn (EquipmentField $field) => [
                        'id' => $field->id,
                        'name' => $field->name,
                        'type' => $field->type,
                        'options' => $field->choices(),
                        'required' => $field->required,
                    ]),
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $type = EquipmentType::create([
            'name' => $data['name'],
            'icon' => $data['icon'] ?? null,
            'has_accessories' => $data['has_accessories'] ?? true,
        ]);

        if (array_key_exists('fields', $data)) {
            $this->syncFields($type, $data['fields']);
        }

        return back();
    }

    public function update(Request $request, EquipmentType $equipment): RedirectResponse
    {
        $data = $this->validated($request, $equipment);

        $equipment->update([
            'name' => $data['name'],
            'icon' => $data['icon'] ?? null,
            'has_accessories' => $data['has_accessories'] ?? $equipment->has_accessories,
        ]);

        // Saying nothing about the fields leaves them alone; sending a list — even
        // an empty one — is what replaces them.
        if (array_key_exists('fields', $data)) {
            $this->syncFields($equipment, $data['fields']);
        }

        return back();
    }

    public function destroy(EquipmentType $equipment): RedirectResponse
    {
        // The units of this kind go with it; employees themselves are untouched.
        $equipment->delete();

        return back();
    }

    /**
     * The fields as the dialog left them: the ones it kept are updated in place,
     * the ones it added are created, and the ones it dropped are deleted along
     * with whatever the units had written in them — which the dialog says before
     * it lets anybody drop one.
     *
     * @param  list<array<string, mixed>>  $fields
     */
    private function syncFields(EquipmentType $type, array $fields): void
    {
        $kept = [];

        foreach (array_values($fields) as $position => $field) {
            $attributes = [
                'name' => trim($field['name']),
                'type' => $field['type'],
                // Only a list has a list of choices; the rest would only keep a
                // stale one around for the day somebody changes the type back.
                'options' => $field['type'] === 'select' ? array_values(array_filter(array_map('trim', $field['options'] ?? []))) : null,
                'required' => (bool) ($field['required'] ?? false),
                'position' => $position,
            ];

            $row = isset($field['id'])
                ? $type->fields()->whereKey($field['id'])->first()
                : null;

            if ($row !== null) {
                $row->update($attributes);
            } else {
                $row = $type->fields()->create($attributes);
            }

            $kept[] = $row->id;
        }

        $type->fields()->whereKeyNot($kept)->delete();
    }

    /**
     * @return array{name: string, icon: string|null, has_accessories?: bool, fields?: list<array<string, mixed>>}
     */
    private function validated(Request $request, ?EquipmentType $type = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('equipment_types', 'name')->ignore($type)],
            // One of the drawings the interface has, or none for the plain box.
            'icon' => ['nullable', Rule::in(EquipmentIcons::KEYS)],
            // Whether a unit of this kind comes with anything at all.
            'has_accessories' => ['sometimes', 'boolean'],

            // A category with nothing of its own is a category all the same,
            // so saying nothing about the fields leaves them as they are.
            'fields' => ['nullable', 'array', 'max:20'],
            // A field already in the category keeps its id, so what the units
            // have written in it survives a rename.
            'fields.*.id' => ['nullable', 'integer', Rule::exists('equipment_fields', 'id')->where('equipment_type_id', $type?->id ?? 0)],
            'fields.*.name' => ['required', 'string', 'max:100', 'distinct:ignore_case'],
            'fields.*.type' => ['required', Rule::in(array_keys(EquipmentField::TYPES))],
            'fields.*.required' => ['boolean'],
            // A list with nothing to choose from is not a list. The question is
            // about a field's type and its choices together, so it is asked of
            // the pair rather than of one value.
            'fields.*.options' => ['array', 'max:30', function (string $attribute, mixed $value, Closure $fail) use ($request) {
                $index = explode('.', $attribute)[1];

                if ($request->input("fields.{$index}.type") === 'select' && array_filter(array_map('trim', (array) $value)) === []) {
                    $fail('Укажите хотя бы один вариант для выбора.');
                }
            }],
            'fields.*.options.*' => ['nullable', 'string', 'max:100'],
        ], attributes: [
            'name' => 'название',
            'icon' => 'иконка',
            'has_accessories' => 'комплектация',
            'fields' => 'поля',
            'fields.*.name' => 'название поля',
            'fields.*.type' => 'тип поля',
            'fields.*.options' => 'варианты',
        ], messages: [
            'fields.*.name.distinct' => 'Два поля с одним названием: карточка прочиталась бы дважды.',
        ]);
    }
}
