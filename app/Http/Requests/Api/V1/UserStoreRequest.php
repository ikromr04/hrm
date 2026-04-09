<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UserStoreRequest extends FormRequest
{
    protected array $addedAttributes = [];

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'data.type' => 'required|in:users',
            'data.attributes.name' => 'required|string|max:255',
            'data.attributes.surname' => 'required|string|max:255',
            'data.attributes.patronymic' => 'nullable|string|max:255',
            'data.attributes.email' => 'required|string|email|max:255|unique:users,email',
            'data.attributes.password' => 'nullable|string|min:6|confirmed',
            'data.attributes.avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            'data.relationships' => 'nullable|array',

            'data.relationships.roles' => 'nullable|array',
            'data.relationships.roles.data' => 'required_with:data.relationships.roles|array',
            'data.relationships.roles.data.*.type' => 'required_with:data.relationships.roles.data|in:roles',
            'data.relationships.roles.data.*.id' => 'required_with:data.relationships.roles.data|exists:roles,id',

            'data.relationships.positions' => 'nullable|array',
            'data.relationships.positions.data' => 'required_with:data.relationships.positions|array',
            'data.relationships.positions.data.*.type' => 'required_with:data.relationships.positions.data|in:positions',
            'data.relationships.positions.data.*.id' => 'required_with:data.relationships.positions.data|exists:positions,id',

            'data.relationships.departments' => 'nullable|array',
            'data.relationships.departments.data' => 'required_with:data.relationships.departments|array',
            'data.relationships.departments.data.*.type' => 'required_with:data.relationships.departments.data|in:departments',
            'data.relationships.departments.data.*.id' => 'required_with:data.relationships.departments.data|exists:departments,id',

            'data.relationships.languages' => 'nullable|array',
            'data.relationships.languages.data' => 'required_with:data.relationships.languages|array',
            'data.relationships.languages.data.*.type' => 'required_with:data.relationships.languages.data|in:languages',
            'data.relationships.languages.data.*.id' => 'required_with:data.relationships.languages.data|exists:languages,id',
        ];
    }

    public function messages(): array
    {
        return [
            'data.attributes.email.unique' => 'Сотрудник с таким Email-ом уже существует.'
        ];
    }

    public function mappedAttributes(): array
    {
        return [
            'name' => $this->input('data.attributes.name'),
            'surname' => $this->input('data.attributes.surname'),
            'patronymic' => $this->input('data.attributes.patronymic'),
            'email' => $this->input('data.attributes.email'),
            'password' => $this->input('data.attributes.password') ?: Str::random(12),
            ...$this->addedAttributes
        ];
    }

    public function mappedRelationships(): array
    {
        $relationships = [];

        foreach (User::RELATIONSHIPS as $relationshipName) {
            if ($this->has("data.relationships.$relationshipName")) {
                $relationships[$relationshipName] = collect(
                    $this->input("data.relationships.$relationshipName.data", [])
                )
                    ->pluck('id')
                    ->map(fn($id) => (int) $id)
                    ->toArray();
            }
        }

        return $relationships;
    }

    public function addAttributes(array $attributes): void
    {
        $this->addedAttributes = [
            ...$this->addedAttributes,
            ...$attributes,
        ];
    }
}
