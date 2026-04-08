<?php

namespace App\Http\Resources\V1;

use App\Traits\Resource\MappedAttributes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
                'userDetails' => (object) [
                    'links' => (object) [
                        'self' => route('users.relationships.user-details', $this->id),
                        'related' => route('users.user-details', $this->id),
                    ],
                ],
                'roles' => (object) [
                    'links' => (object) [
                        'self' => route('users.relationships.roles', $this->id),
                        'related' => route('users.roles', $this->id),
                    ],
                ],
                'positions' => (object) [
                    'links' => (object) [
                        'self' => route('users.relationships.positions', $this->id),
                        'related' => route('users.positions', $this->id),
                    ],
                ],
                'departments' => (object) [
                    'links' => (object) [
                        'self' => route('users.relationships.departments', $this->id),
                        'related' => route('users.departments', $this->id),
                    ],
                ],
                'experiences' => (object) [
                    'links' => (object) [
                        'self' => route('users.relationships.experiences', $this->id),
                        'related' => route('users.experiences', $this->id),
                    ],
                ],
                'educations' => (object) [
                    'links' => (object) [
                        'self' => route('users.relationships.educations', $this->id),
                        'related' => route('users.educations', $this->id),
                    ],
                ],
                'equipments' => (object) [
                    'links' => (object) [
                        'self' => route('users.relationships.equipments', $this->id),
                        'related' => route('users.equipments', $this->id),
                    ],
                ],
                'languages' => (object) [
                    'links' => (object) [
                        'self' => route('users.relationships.languages', $this->id),
                        'related' => route('users.languages', $this->id),
                    ],
                ],
            ],

            'links' => [
                'self' => route('users.show', $this->id),
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
                'self' => route('users.show', $this->id),
            ]
        ];
    }
}
