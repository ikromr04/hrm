<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ExperienceStoreRequest extends FormRequest
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
            'data.type' => 'required|in:experiences',
            'data.attributes.companyName' => 'required|string|max:255',
            'data.attributes.position' => 'required|string|max:255',
            'data.attributes.startedAt' => 'required|date|before:today',
            'data.attributes.endedAt' => 'required|date|before:today',

            'data.relationships.user.data.type' => 'required|in:users',
            'data.relationships.user.data.id' => 'required|exists:users,id',
        ];
    }

    public function mappedAttributes(): array
    {
        return [
            'user_id' => $this->input('data.relationships.user.data.id'),
            'company_name' => $this->input('data.attributes.companyName'),
            'position' => $this->input('data.attributes.position'),
            'started_at' => $this->input('data.attributes.startedAt'),
            'ended_at' => $this->input('data.attributes.endedAt'),
        ];
    }
}
