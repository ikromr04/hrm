<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ExperienceUpdateRequest extends FormRequest
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
            'data.id' => "required|in:{$this->route('experience')->id}",
            'data.attributes.companyName' => 'sometimes|required|string|max:255',
            'data.attributes.position' => 'sometimes|required|string|max:255',
            'data.attributes.startedAt' => 'sometimes|required|date|before:today',
            'data.attributes.endedAt' => 'sometimes|required|date|before:today',
        ];
    }

    public function mappedAttributes(): array
    {
        return collect([
            'user_id' => 'data.relationships.user.data.id',
            'company_name' => 'data.attributes.companyName',
            'position' => 'data.attributes.position',
            'started_at' => 'data.attributes.startedAt',
            'ended_at' => 'data.attributes.endedAt',
        ])
            ->filter(fn($path) => $this->exists($path))
            ->mapWithKeys(fn($path, $key) => [$key => $this->input($path)])
            ->toArray();
    }
}
