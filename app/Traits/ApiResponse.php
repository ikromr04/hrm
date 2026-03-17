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
trait ApiResponse
{
    /**
     * Return a successful JSON:API response.
     *
     * Example response:
     * {
     *   "jsonapi": { "version": "1.1" },
     *   "data": {...}
     * }
     */
    protected function success(
        mixed $data,
        int $status = Response::HTTP_OK,
        ?array $links = null
    ): JsonResponse {
        $response = [
            'jsonapi' => ['version' => config('jsonapi.version')],
            'data' => $data
        ];

        if ($links) {
            $response['links'] = $links;
        }

        return response()->json($response, $status);
    }

    /**
     * Return a JSON:API compliant error response.
     *
     * Each error item must contain:
     * - status (int|string)  HTTP status code
     * - title  (string)      Short, human-readable summary
     * - detail (string|null) Detailed explanation
     */
    public function error(array $errors): JsonResponse
    {
        $status = (int) ($errors[0]['status'] ?? 500);

        return response()->json([
            'jsonapi' => ['version' => config('jsonapi.version')],
            'errors'  => array_map(fn($error) => [
                'status' => (string) $error['status'],
                'title'  => $error['title'],
                'detail' => $error['detail'] ?? null
            ], $errors),
        ], $status);
    }
}
