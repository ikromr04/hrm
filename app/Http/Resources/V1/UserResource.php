<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;

class UserResource extends JsonApiResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'users',
            'id' => (string) $this->id,

            'attributes' => $this->mappedAttributes(),

            'links' => [
                'self' => 'Not Implemented'
            ],
        ];
    }

    /**
     * Get any additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'links' => [
                'self' => $request->fullUrl()
            ]
        ];
    }
}
