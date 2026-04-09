<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TokenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'tokens',
            'id' => (string) $this->accessToken->id,

            'attributes' => [
                'token' => $this->plainTextToken,
            ],

            'links' => [
                'self' => route('login'),
            ],
        ];
    }
}
