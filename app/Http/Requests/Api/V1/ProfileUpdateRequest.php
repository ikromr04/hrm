<?php

namespace App\Http\Requests\Api\V1;

class ProfileUpdateRequest extends ProfileBaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
