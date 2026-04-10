<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'departments',
            'id' => (string) $this->id,

            'attributes' => [
                'name' => $this->name,
                'left' => $this->_lft,
                'right' => $this->_rgt,
                'parent' => $this->parent_id,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
            ],

            'links' => [
                'self' => route('departments.show', $this->id),
            ]
        ];
    }
}
