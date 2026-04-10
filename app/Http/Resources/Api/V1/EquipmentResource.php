<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'equipments',
            'id' => (string) $this->id,

            'attributes' => [
                'name' => $this->name,
                'description' => $this->description,
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
                'self' => route('equipments.show', $this->id),
            ]
        ];
    }
}
