<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EquipmentStoreRequest extends FormRequest
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
            'data.type' => 'required|in:equipments',
            'data.attributes.name' => 'required|string|max:255',
            'data.attributes.description' => 'nullable|string|max:255',

            'data.relationships.user.data.type' => 'required|in:users',
            'data.relationships.user.data.id' => 'required|exists:users,id',
        ];
    }

    public function mappedAttributes(): array
    {
        return [
            'user_id' => $this->input('data.relationships.user.data.id'),
            'name' => $this->input('data.attributes.name'),
            'description' => $this->input('data.attributes.description'),
        ];
    }
}
