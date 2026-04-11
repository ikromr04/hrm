<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EducationStoreRequest extends FormRequest
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
            'data.type' => 'required|in:educations',
            'data.attributes.institution' => 'required|string|max:255',
            'data.attributes.faculty' => 'required|string|max:255',
            'data.attributes.speciality' => 'required|string|max:255',
            'data.attributes.form' => 'required|string|max:255',
            'data.attributes.startedAt' => 'required|date|before:today',
            'data.attributes.endedAt' => 'nullable|date',

            'data.relationships.user.data.type' => 'required|in:users',
            'data.relationships.user.data.id' => 'required|exists:users,id',
        ];
    }

    public function mappedAttributes(): array
    {
        return [
            'user_id' => $this->input('data.relationships.user.data.id'),
            'institution' => $this->input('data.attributes.institution'),
            'faculty' => $this->input('data.attributes.faculty'),
            'speciality' => $this->input('data.attributes.speciality'),
            'form' => $this->input('data.attributes.form'),
            'started_at' => $this->input('data.attributes.startedAt'),
            'ended_at' => $this->input('data.attributes.endedAt'),
        ];
    }
}
