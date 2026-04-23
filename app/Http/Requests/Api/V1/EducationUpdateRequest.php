<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EducationUpdateRequest extends FormRequest
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
            'data.id' => "required|exists:educations,id|in:{$this->route('education')->id}",
            'data.attributes.institution' => 'sometimes|required|string|max:255',
            'data.attributes.faculty' => 'sometimes|required|string|max:255',
            'data.attributes.speciality' => 'sometimes|required|string|max:255',
            'data.attributes.form' => 'sometimes|required|string|max:255',
            'data.attributes.startedAt' => 'sometimes|required|date|before:today',
            'data.attributes.endedAt' => 'sometimes|nullable|date',
        ];
    }

    public function mappedAttributes(): array
    {
        return collect([
            'institution' => 'data.attributes.institution',
            'faculty' => 'data.attributes.faculty',
            'speciality' => 'data.attributes.speciality',
            'form' => 'data.attributes.form',
            'started_at' => 'data.attributes.startedAt',
            'ended_at' => 'data.attributes.endedAt',
        ])
            ->filter(fn($path) => $this->exists($path))
            ->mapWithKeys(fn($path, $key) => [$key => $this->input($path)])
            ->toArray();
    }
}
