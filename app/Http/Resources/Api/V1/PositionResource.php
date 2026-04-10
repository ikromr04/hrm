<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'positions',
            'id' => (string) $this->id,

            'attributes' => [
                'name' => $this->name,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
            ],

            'links' => [
                'self' => route('positions.show', $this->id),
            ]
        ];
    }
}
