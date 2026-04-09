<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends UserBaseRequest
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
            'data.type' => 'required|in:users',
            'data.id' => "required|exists:users,id|in:$this->route('user')",
            'data.attributes.name' => 'nullable|string|max:255',
            'data.attributes.surname' => 'nullable|string|max:255',
            'data.attributes.patronymic' => 'nullable|string|max:255',
            'data.attributes.email' => "nullable|string|email|max:255|unique:users,email,$this->route('user')",
            'data.attributes.password' => 'nullable|string|min:6|confirmed',
            'data.attributes.avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            ...$this->relationshipRules
        ];
    }
}
