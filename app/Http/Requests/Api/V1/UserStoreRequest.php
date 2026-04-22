<?php

namespace App\Http\Requests\Api\V1;

class UserStoreRequest extends UserBaseRequest
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
            'data.type' => 'required|in:users',
            'data.attributes.name' => 'required|string|max:255',
            'data.attributes.surname' => 'required|string|max:255',
            'data.attributes.patronymic' => 'sometimes|nullable|string|max:255',
            'data.attributes.email' => 'required|string|email|max:255|unique:users,email',
            'data.attributes.password' => 'sometimes|nullable|string|min:6|confirmed',
            'data.attributes.avatar' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            ...$this->relationshipRules
        ];
    }
}
