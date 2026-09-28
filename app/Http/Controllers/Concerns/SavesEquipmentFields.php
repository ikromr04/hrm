<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Equipment;
use App\Models\EquipmentField;
use App\Models\EquipmentFieldValue;
use App\Models\EquipmentType;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * The fields a category gives its units, read off a form and written down.
 *
 * A form sends them as "fields[<field id>]", so the rules are built for the
 * category the form was filled in for: a monitor is never asked for a processor,
 * and a required field is required only where it exists.
 */
trait SavesEquipmentFields
{
    /**
     * Rules for the fields of one category, keyed the way the form sends them.
     *
     * @return array<string, mixed>
     */
    protected function fieldRules(?EquipmentType $type): array
    {
        if ($type === null) {
            return ['fields' => ['nullable', 'array']];
        }

        $rules = ['fields' => ['nullable', 'array']];

        foreach ($type->fields as $field) {
            $rules["fields.{$field->id}"] = [
                $field->required ? 'required' : 'nullable',
                ...match ($field->type) {
                    'number' => ['numeric'],
                    'date' => ['date'],
                    'boolean' => ['boolean'],
                    'select' => ['string', Rule::in($field->choices())],
                    default => ['string', 'max:200'],
                },
            ];
        }

        return $rules;
    }

    /**
     * What each field is called, so a message names the field rather than its id.
     *
     * @return array<string, string>
     */
    protected function fieldAttributes(?EquipmentType $type): array
    {
        if ($type === null) {
            return [];
        }

        return $type->fields
            ->mapWithKeys(fn (EquipmentField $field) => ["fields.{$field->id}" => mb_strtolower($field->name)])
            ->all();
    }

    /**
     * Write the values down, one row per field that has one.
     *
     * A field left empty keeps no row: "nothing written here" and "no row" are
     * the same thing, and it saves the card from reading blanks. Values of
     * fields that belong to another category are left where they are — a unit
     * filed under the wrong category and put back should not lose its data.
     *
     * @param  array<int|string, mixed>  $values  As the form sent them: field id => value.
     * @return array<string, array{string|null, string|null}> What changed, by field name.
     */
    protected function saveFields(Equipment $equipment, array $values): array
    {
        $fields = $equipment->type?->fields ?? collect();
        $existing = $equipment->fieldValues()->get()->keyBy('equipment_field_id');
        $changes = [];

        foreach ($fields as $field) {
            // A field the form did not mention is left alone; one it mentioned
            // as empty is cleared.
            if (! array_key_exists($field->id, $values) && ! Arr::has($values, (string) $field->id)) {
                continue;
            }

            $was = $existing->get($field->id)?->value;
            $now = $this->plain($field, $values[$field->id] ?? null);

            if ($was === $now) {
                continue;
            }

            if ($now === null) {
                $existing->get($field->id)?->delete();
            } else {
                EquipmentFieldValue::updateOrCreate(
                    ['equipment_id' => $equipment->id, 'equipment_field_id' => $field->id],
                    ['value' => $now],
                );
            }

            // Before and after, in the shape the journal keeps every change in,
            // and under the field's own name — there is no column to name it by.
            $changes[$field->name] = [$field->read($was), $field->read($now)];
        }

        return $changes;
    }

    /** One value as it is kept: text, or nothing at all when it is empty. */
    private function plain(EquipmentField $field, mixed $value): ?string
    {
        if ($field->type === 'boolean') {
            // An unticked box arrives as false, which is a value like any other.
            return $value === null || $value === '' ? null : ($value ? '1' : '0');
        }

        $value = is_string($value) ? trim($value) : $value;

        return $value === null || $value === '' ? null : (string) $value;
    }
}
