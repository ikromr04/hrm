<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\EmployeeFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The "Семья" card of the profile: marital status, the spouse and the children.
 *
 * The block is given line by line, so the route lets in whoever may change any
 * one of the three. A line this viewer may not change has no rules here at all,
 * which keeps it out of validated() and so out of the save: an empty spouse or
 * an empty list of children sent by somebody who may only set the marital status
 * must not wipe what HR has on file.
 */
class UpdateFamilyRequest extends FormRequest
{
    /**
     * The route already requires the right to change something in the block.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $employee */
        $employee = $this->route('employee');
        // editableBy() picks the scope itself: one's own card is read against the
        // profile rights, anybody else's against the employees ones.
        $editable = EmployeeFields::editableBy($this->user(), $employee);

        $rules = [];

        if (in_array('marital_status', $editable, true)) {
            $rules['marital_status'] = ['nullable', Rule::in(['single', 'married'])];
        }

        if (in_array('spouse', $editable, true)) {
            $rules['spouse_name'] = ['nullable', 'string', 'max:150'];
            $rules['spouse_birth_date'] = ['nullable', 'date', 'before_or_equal:today'];
        }

        if (in_array('children', $editable, true)) {
            // Only false carries meaning here ("HR says there are none"); the true
            // case is derived from the rows below, so the two cannot drift.
            $rules['has_children'] = ['nullable', 'boolean'];
            $rules['children'] = ['present', 'array', 'max:20'];
            $rules['children.*.full_name'] = ['required', 'string', 'max:150'];
            $rules['children.*.birth_date'] = ['nullable', 'date', 'before_or_equal:today'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'marital_status' => 'семейное положение',
            'spouse_name' => 'ФИО супруга',
            'spouse_birth_date' => 'дата рождения супруга',
            'children.*.full_name' => 'ФИО ребёнка',
            'children.*.birth_date' => 'дата рождения ребёнка',
        ];
    }
}
