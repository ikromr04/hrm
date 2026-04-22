<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;

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
            'data.id' => "required|exists:users,id|in:{$this->route('user')->id}",
            'data.attributes.name' => 'sometimes|required|string|max:255',
            'data.attributes.surname' => 'sometimes|required|string|max:255',
            'data.attributes.patronymic' => 'sometimes|nullable|string|max:255',
            'data.attributes.avatar' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'data.attributes.email' => 'sometimes|required|email|max:255|unique:users,email',
            'data.attributes.password' => 'sometimes|required|string|min:6|confirmed',

            ...$this->relationshipRules
        ];
    }
}
