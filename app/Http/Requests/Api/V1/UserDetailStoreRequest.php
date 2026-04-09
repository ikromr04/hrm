<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\FamilyStatus;
use App\Enums\Sex;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UserDetailStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'data.type' => 'required|in:user-details',
            'data.attributes.birthDate' => 'nullable|date|before:today',
            'data.attributes.sex' => 'nullable|in:' . implode(',', Sex::values()),
            'data.attributes.nationality' => 'nullable|string|max:255',
            'data.attributes.citizenship' => 'nullable|string|max:255',
            'data.attributes.address' => 'nullable|string|max:255',
            'data.attributes.tel1' => 'nullable|string|max:255',
            'data.attributes.tel2' => 'nullable|string|max:255',
            'data.attributes.familyStatus' => 'nullable|in:' . implode(',', FamilyStatus::values()),
            'data.attributes.children' => 'nullable|array',
            'data.attributes.children.*' => 'integer|digits:4|min:1900|max:' . date('Y'),
            'data.attributes.startedWorkAt' => 'nullable|date|before_or_equal:today',

            'data.relationships.user.data.type' => 'required|in:users',
            'data.relationships.user.data.id' => 'required|exists:users,id|unique:user_details,user_id',
        ];
    }

    public function mappedAttributes(): array
    {
        return [
            'user_id' => $this->input('data.relationships.user.data.id'),
            'birth_date' => $this->input('data.attributes.birthDate'),
            'sex' => $this->input('data.attributes.sex'),
            'nationality' => $this->input('data.attributes.nationality'),
            'citizenship' => $this->input('data.attributes.citizenship'),
            'address' => $this->input('data.attributes.address'),
            'tel_1' => $this->input('data.attributes.tel1'),
            'tel_2' => $this->input('data.attributes.tel2'),
            'family_status' => $this->input('data.attributes.familyStatus'),
            'children' => $this->input('data.attributes.children'),
            'started_work_at' => $this->input('data.attributes.startedWorkAt'),
        ];
    }
}
