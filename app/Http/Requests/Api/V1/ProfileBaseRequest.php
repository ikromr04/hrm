<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\FamilyStatus;
use App\Enums\Sex;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileBaseRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'data.type' => 'required|in:profiles',
            'data.attributes.birthDate' => 'sometimes|nullable|date|before:today',
            'data.attributes.sex' => 'sometimes|nullable|in:' . implode(',', Sex::values()),
            'data.attributes.nationality' => 'sometimes|nullable|string|max:255',
            'data.attributes.citizenship' => 'sometimes|nullable|string|max:255',
            'data.attributes.address' => 'sometimes|nullable|string|max:255',
            'data.attributes.tel1' => 'sometimes|nullable|string|max:255',
            'data.attributes.tel2' => 'sometimes|nullable|string|max:255',
            'data.attributes.familyStatus' => 'sometimes|nullable|in:' . implode(',', FamilyStatus::values()),
            'data.attributes.children' => 'sometimes|nullable|array',
            'data.attributes.children.*' => 'integer|digits:4|min:1900|max:' . date('Y'),
            'data.attributes.startedWorkAt' => 'sometimes|nullable|date|before_or_equal:today',
        ];

        if ($this->routeIs('profiles.store')) {
            $rules['data.relationships.user.data.type'] = 'required|in:users';
            $rules['data.relationships.user.data.id'] = 'required|exists:users,id|unique:profiles,user_id';
        }

        if ($this->routeIs('profiles.update')) {
            $rules['data.id'] = "required|exists:profiles,id|in:{$this->route('profile')->id}";
        }

        return $rules;
    }

    public function mappedAttributes(): array
    {
        return collect([
            'user_id' => 'data.relationships.user.data.id',
            'birth_date' => 'data.attributes.birthDate',
            'sex' => 'data.attributes.sex',
            'nationality' => 'data.attributes.nationality',
            'citizenship' => 'data.attributes.citizenship',
            'address' => 'data.attributes.address',
            'tel_1' => 'data.attributes.tel1',
            'tel_2' => 'data.attributes.tel2',
            'family_status' => 'data.attributes.familyStatus',
            'children' => 'data.attributes.children',
            'started_work_at' => 'data.attributes.startedWorkAt',
        ])
            ->filter(function ($path, $key) {
                if ($key === 'user_id' && $this->routeIs('profiles.update')) {
                    return false;
                }

                return $this->exists($path);
            })
            ->mapWithKeys(fn($path, $key) => [$key => $this->input($path)])
            ->toArray();
    }
}
