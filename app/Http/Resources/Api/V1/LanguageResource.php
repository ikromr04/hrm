<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LanguageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'languages',
            'id' => (string) $this->id,

            'attributes' => [
                'name' => $this->name,
                'level' => $this->level,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
            ],

            'links' => [
                'self' => route('languages.show', $this->id),
            ]
        ];
    }
}
