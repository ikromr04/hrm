<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EducationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'educations',
            'id' => (string) $this->id,

            'attributes' => [
                'institution' => $this->institution,
                'faculty' => $this->faculty,
                'speciality' => $this->speciality,
                'form' => $this->form,
                'startedAt' => $this->started_at,
                'endedAt' => $this->ended_at,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
            ],

            'relationships' => [
                'user' => [
                    'data' =>  [
                        'type' => 'users',
                        'id' => (string) $this->user_id,
                    ],
                ],
            ],
        ];
    }
}
