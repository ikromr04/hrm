<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'data.type' => 'required|in:tokens',
            'data.attributes.email' => 'required|string|email|max:150',
            'data.attributes.password' => 'required|string|min:6|max:50',
        ];
    }

    public function credentials(): array
    {
        return [
            'email' => $this->input('data.attributes.email'),
            'password' => $this->input('data.attributes.password'),
        ];
    }

    public function messages(): array
    {
        return [
            'data.type.required' => 'Тип ресурса обязателен.',
            'data.type.in' => 'Неверный тип ресурса.',

            'data.attributes.email.required' => 'Email обязателен.',
            'data.attributes.email.string' => 'Email должен быть строкой.',
            'data.attributes.email.email' => 'Некорректный формат email.',
            'data.attributes.email.max' => 'Email не должен превышать 150 символов.',

            'data.attributes.password.required' => 'Пароль обязателен.',
            'data.attributes.password.string' => 'Пароль должен быть строкой.',
            'data.attributes.password.min' => 'Пароль должен быть не менее 6 символов.',
            'data.attributes.password.max' => 'Пароль не должно превышать 50 символов.',
        ];
    }
}
