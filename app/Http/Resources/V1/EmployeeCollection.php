<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\JsonApiCollection;
use Illuminate\Http\Request;

class EmployeeCollection extends JsonApiCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }

    public function with(Request $request): array
    {
        return [
            'links' => [
                'self' => $request->fullUrl()
            ]
        ];
    }
}
