<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class JsonApiResource extends JsonResource
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toResponse($request): JsonResponse
    {
        $response = [
            'jsonapi' => [
                'version' => config('jsonapi.version'),
            ],
            'data' => $this->toArray($request),
        ];

        if (method_exists($this, 'with')) {
            $extra = $this->with($request);

            if (! empty($extra)) {
                $response = array_merge($response, $extra);
            }
        }

        return response()->json($response);
    }

    /**
     * Get model attributes as an object with camelCase keys.
     */
    public function mappedAttributes(): object
    {
        $attributes = collect($this->attributesToArray())
            ->except(['id'])
            ->mapWithKeys(fn($value, $key) => [Str::camel($key) => $value])
            ->toArray();

        return (object) $attributes;
    }
}
