<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'user-details',
            'id' => (string) $this->id,

            'attributes' => [
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
            ],

            'relationships' => [
                'user' => [
                    'data' =>  [
                        'type' => 'users',
                        'id' => $this->user_id,
                    ],
                    'links' => [
                        'related' => route('users.show', $this->user_id),
                    ],
                ],
            ],

            'links' => [
                'self' => route('user-details.show', $this->id),
            ],
        ];
    }
}
