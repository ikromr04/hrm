<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait ApiResponse
 *
 * Provides standardized JSON:API (v1.1) compliant responses
 *
 * @see https://jsonapi.org/format/
 */
trait ApiResponses
{
    /**
     * Return an unauthorized JSON:API response.
     */
    protected function unauthorized(?string $message = null): JsonResponse
    {
        return $this->error([[
            'status' => Response::HTTP_UNAUTHORIZED,
            'title' => __('api.401.title'),
            'detail' => $message ?: __('api.401.message'),
        ]]);
    }

    /**
     * Return a JSON:API compliant error response.
     */
    public function error(array $errors): JsonResponse
    {
        $status = (int) ($errors[0]['status'] ?? 500);

        return response()->json([
            'errors' => array_map(function ($error) {
                return array_filter([
                    'status' => (string) $error['status'],
                    'title'  => $error['title'],
                    'detail' => $error['detail'] ?? null,
                    'source' => $error['source'] ?? null,
                ], fn($value) => $value !== null);
            }, $errors),
        ], $status);
    }
}
