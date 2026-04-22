<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UserBaseRequest extends FormRequest
{
    protected array $relationshipRules = [
        'data.relationships' => 'nullable|array',

        'data.relationships.roles' => 'nullable|array',
        'data.relationships.roles.data' => 'array',
        'data.relationships.roles.data.*.type' => 'in:roles',
        'data.relationships.roles.data.*.id' => 'exists:roles,id',

        'data.relationships.positions' => 'nullable|array',
        'data.relationships.positions.data' => 'array',
        'data.relationships.positions.data.*.type' => 'in:positions',
        'data.relationships.positions.data.*.id' => 'exists:positions,id',

        'data.relationships.departments' => 'nullable|array',
        'data.relationships.departments.data' => 'array',
        'data.relationships.departments.data.*.type' => 'in:departments',
        'data.relationships.departments.data.*.id' => 'exists:departments,id',

        'data.relationships.languages' => 'nullable|array',
        'data.relationships.languages.data' => 'array',
        'data.relationships.languages.data.*.type' => 'in:languages',
        'data.relationships.languages.data.*.id' => 'exists:languages,id',
    ];

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

    public function mappedAttributes(): array
    {
        $attributes = collect([
            'name' => $this->input('data.attributes.name'),
            'surname' => $this->input('data.attributes.surname'),
            'patronymic' => $this->input('data.attributes.patronymic'),
            'email' => $this->input('data.attributes.email'),
            'password' => $this->input('data.attributes.password'),
        ])->filter(function ($value, $key) {
            return $this->exists("data.attributes.$key");
        })->toArray();

        if ($this->routeIs('users.store')) {
            $attributes['password'] = $this->input('data.attributes.password') ?: Str::random(12);
        }

        return $attributes;
    }

    public function messages(): array
    {
        return [
            'data.type.required' => 'Поле type обязательно.',
            'data.type.in' => 'Тип должен быть "users".',

            'data.attributes.name.required' => 'Имя обязательно.',
            'data.attributes.name.string' => 'Имя должно быть строкой.',
            'data.attributes.name.max' => 'Имя не должно превышать 255 символов.',

            'data.attributes.surname.required' => 'Фамилия обязательна.',
            'data.attributes.surname.string' => 'Фамилия должна быть строкой.',
            'data.attributes.surname.max' => 'Фамилия не должна превышать 255 символов.',

            'data.attributes.patronymic.string' => 'Отчество должно быть строкой.',
            'data.attributes.patronymic.max' => 'Отчество не должно превышать 255 символов.',

            'data.attributes.email.required' => 'Email обязателен.',
            'data.attributes.email.string' => 'Email должен быть строкой.',
            'data.attributes.email.email' => 'Введите корректный email.',
            'data.attributes.email.max' => 'Email не должен превышать 255 символов.',
            'data.attributes.email.unique' => 'Пользователь с таким email-ом уже существует.',

            'data.attributes.password.string' => 'Пароль должен быть строкой.',
            'data.attributes.password.min' => 'Пароль должен быть не менее 6 символов.',
            'data.attributes.password.confirmed' => 'Подтверждение пароля не совпадает.',

            'data.attributes.avatar.image' => 'Файл должен быть изображением.',
            'data.attributes.avatar.mimes' => 'Допустимые форматы: jpg, jpeg, png, webp.',
            'data.attributes.avatar.max' => 'Размер изображения не должен превышать 2 МБ.',
        ];
    }
}
