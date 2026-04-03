<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UserStoreRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'data.type' => ['required', 'in:users'],
            'data.attributes.name' => ['required', 'string', 'max:50'],
            'data.attributes.surname' => ['required', 'string', 'max:50'],
            'data.attributes.email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'data.attributes.password' => ['nullable', 'string', 'min:6', 'max:50', 'confirmed'],
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
        ];
    }

    public function messages(): array
    {
        return [
            'data.type.required' => 'Тип ресурса обязателен.',
            'data.type.in' => 'Неверный тип ресурса.',

            'data.attributes.name.required' => 'Имя обязательно.',
            'data.attributes.name.string' => 'Имя должно быть строкой.',
            'data.attributes.name.max' => 'Имя не должно превышать 50 символов.',

            'data.attributes.surname.required' => 'Фамилия обязательна.',
            'data.attributes.surname.string' => 'Фамилия должна быть строкой.',
            'data.attributes.surname.max' => 'Фамилия не должна превышать 50 символов.',

            'data.attributes.patronymic.string' => 'Отчество должно быть строкой.',
            'data.attributes.patronymic.max' => 'Отчество не должно превышать 50 символов.',

            'data.attributes.email.required' => 'Email обязателен.',
            'data.attributes.email.string' => 'Email должен быть строкой.',
            'data.attributes.email.email' => 'Некорректный формат email.',
            'data.attributes.email.max' => 'Email не должен превышать 150 символов.',
            'data.attributes.email.unique' => 'Такой email уже используется.',

            'data.attributes.password.string' => 'Пароль должен быть строкой.',
            'data.attributes.password.min' => 'Пароль должен быть не менее 6 символов.',
            'data.attributes.password.confirmed' => 'Пароль и подтверждение пароля не совпадают.',
            'data.attributes.password.max' => 'Пароль не должно превышать 50 символов.',
        ];
    }
}
