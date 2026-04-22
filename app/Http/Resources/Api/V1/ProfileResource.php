<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class ProfileResource extends JsonApiResource
{
    public $relationships = [
        'user' => UserResource::class,
    ];

    public function toAttributes(Request $request): array
    {
        return [
            'birthDate' => $this->birth_date,
            'sex' => $this->sex,
            'nationality' => $this->nationality,
            'citizenship' => $this->citizenship,
            'address' => $this->address,
            'tel1' => $this->tel_1,
            'tel2' => $this->tel_2,
            'familyStatus' => $this->family_status,
            'children' => $this->children,
            'startedWorkAt' => $this->started_work_at,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
