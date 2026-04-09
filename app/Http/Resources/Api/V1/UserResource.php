<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
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

            'attributes' => [
                'name' => $this->name,
                'surname' => $this->surname,
                'patronymic' => $this->patronymic,
                'avatar' => $this->avatar,
                'avatarThumb' => $this->avatar_thumb,
                'email' => $this->email,
                'emailVerifiedAt' => $this->email_verified_at,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
                'deletedAt' => $this->deleted_at,
            ],

            'links' => [
                'self' => route('users.show', $this->id),
            ],
        ];
    }
}
