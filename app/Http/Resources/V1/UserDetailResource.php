<?php

namespace App\Http\Resources\V1;

use App\Traits\Resource\MappedAttributes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDetailResource extends JsonResource
{
    use MappedAttributes;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'users',
            'id' => (string) $this->id,

            'attributes' => $this->mappedAttributes(),

            'relationships' => (object) [
                'user' => (object)[
                    'data' => (object) [
                        'type' => 'users',
                        'id' => $this->user_id,
                    ],
                    'links' => (object)[
                        'related' => route('users.show', $this->user_id),
                    ],
                ],
            ],

            'links' => [
                'self' => route('user-details.show', $this->id),
            ],
        ];
    }

    /**
     * The additional data that should be added to the top-level resource array.
     *
     * @var array
     */
    public function with(Request $request): array
    {
        return [
            'links' => [
                'self' => route('user-details.show', $this->id),
            ]
        ];
    }
}
