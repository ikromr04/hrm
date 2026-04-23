<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EquipmentUpdateRequest extends FormRequest
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
            'data.id' => "required|exists:equipments,id|in:{$this->route('equipment')->id}",
            'data.attributes.name' => 'sometimes|required|string|max:255',
            'data.attributes.description' => 'sometimes|string|max:255',
        ];
    }

    public function mappedAttributes(): array
    {
        return collect([
            'name' => 'data.attributes.name',
            'description' => 'data.attributes.description',
        ])
            ->filter(fn($path) => $this->exists($path))
            ->mapWithKeys(fn($path, $key) => [$key => $this->input($path)])
            ->toArray();
    }
}
