<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class EducationResource extends JsonApiResource
{
    public $relationships = [
        'user' => UserResource::class,
    ];

    public function toAttributes(Request $request): array
    {
        return [
            'institution' => $this->institution,
            'faculty' => $this->faculty,
            'speciality' => $this->speciality,
            'form' => $this->form,
            'startedAt' => $this->started_at,
            'endedAt' => $this->ended_at,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
